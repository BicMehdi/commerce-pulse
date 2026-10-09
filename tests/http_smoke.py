"""Black-box tests against a local demo, never a production URL."""
import argparse
import http.cookiejar
import json
import os
import re
import unittest
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE="http://127.0.0.1:8090"
class Client:
    def __init__(self):
        self.jar=http.cookiejar.CookieJar()
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
        self.headers={}
    def request(self,path,data=None):
        request=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
        try: response=self.opener.open(request,timeout=30)
        except urllib.error.HTTPError as error: response=error
        self.headers=response.headers
        return response.status,response.read().decode(),response.geturl()
    def token(self,path):
        code,body,_=self.request(path); assert code==200,(code,body[:100])
        return re.search(r'name="_csrf" value="([a-f0-9]+)"',body).group(1)
    def register(self):
        email=f"http-{uuid.uuid4().hex}@commercepulse.test"
        code,body,_=self.request('/register',{'_csrf':self.token('/register'),'name':'HTTP Test','email':email,'password':'Http-Test-2026!','password_confirmation':'Http-Test-2026!'})
        assert code==200 and 'Mon espace' in body
        return email
    def login_admin(self):
        code,body,_=self.request('/login',{'_csrf':self.token('/login'),'email':'admin@commercepulse.test','password':os.getenv('DEMO_PASSWORD','Demo-Pulse-2026!')})
        assert code==200 and 'Vue d’ensemble' in body
    def products(self,query=''):
        return json.loads(self.request('/api/products'+query)[1])['data']
    def archive_product(self,product_id):
        if self.request(f'/products/{product_id}')[0]==404: return None
        return self.request(f'/admin/products/{product_id}/toggle',{'_csrf':self.token('/admin/products')})
    def checkout_payload(self):
        code,body,_=self.request('/cart'); assert code==200
        return {'_csrf':re.search(r'name="_csrf" value="([a-f0-9]+)"',body).group(1),
                'checkout_key':re.search(r'name="checkout_key" value="([a-f0-9]+)"',body).group(1)}
    def add_product(self,product_id,quantity=1):
        return self.request('/cart/add',{'_csrf':self.token(f'/products/{product_id}'),'product_id':product_id,'quantity':quantity})

class HttpTests(unittest.TestCase):
    def fixture_product(self,admin,stock=7):
        form=admin.request('/admin/products/new')[1]
        payload={'_csrf':admin.token('/admin/products/new'),'name':'Edge product '+uuid.uuid4().hex[:10],
                 'description':'An isolated fixture for customer and inventory edge cases.',
                 'price':'17.95','stock':stock,'category_id':re.search(r'<option value="(\d+)"',form).group(1),'illustration':'notebook'}
        self.assertEqual(admin.request('/admin/products',payload)[0],200)
        product=admin.products('?q='+urllib.parse.quote(payload['name']))[0]
        self.addCleanup(admin.archive_product,product['id'])
        return product,payload

    def test_health_and_product_contract(self):
        client=Client(); code,body,_=client.request('/health'); self.assertEqual(code,200)
        self.assertEqual(json.loads(body)['status'],'ok')
        code,body,_=client.request('/api/products?sort=price_asc'); self.assertEqual(code,200)
        products=json.loads(body)['data']; self.assertGreaterEqual(len(products),8)
        self.assertEqual([p['price_cents'] for p in products],sorted(p['price_cents'] for p in products))
    def test_csrf_and_admin_permissions(self):
        client=Client(); self.assertEqual(client.request('/admin')[0],403)
        self.assertEqual(client.request('/cart/add',{'product_id':1,'quantity':1})[0],419)
        client.register(); self.assertEqual(client.request('/admin/products')[0],403)
        self.assertEqual(client.request('/admin/sales.csv')[0],403)
    def test_checkout_retry_and_order_ownership(self):
        client=Client(); client.register()
        products=json.loads(client.request('/api/products')[1])['data']; product=next(p for p in products if p['stock']>1)
        token=client.token('/products/'+str(product['id'])); client.request('/cart/add',{'_csrf':token,'product_id':product['id'],'quantity':1,'price_cents':1})
        _,body,_=client.request('/cart'); key=re.search(r'name="checkout_key" value="([a-f0-9]+)"',body).group(1)
        token=re.search(r'name="_csrf" value="([a-f0-9]+)"',body).group(1)
        payload={'_csrf':token,'checkout_key':key}
        code,body,url=client.request('/checkout',payload); self.assertEqual(code,200); self.assertRegex(url,r'/orders/\d+$')
        expected=f"{product['price_cents']/100:.2f}".replace('.',',')+' €'
        self.assertIn(expected,body)
        self.assertEqual(client.request('/checkout',payload)[2],url)
        other=Client(); other.register(); self.assertEqual(other.request(urllib.parse.urlparse(url).path)[0],404)
    def test_admin_reports_and_refresh(self):
        client=Client(); client.login_admin()
        self.assertEqual(client.request('/admin/analytics')[0],200)
        code,body,_=client.request('/admin/sales.csv'); self.assertEqual(code,200)
        self.assertTrue(body.startswith('order_id,customer_id,')); self.assertNotIn('customer_email',body)
        code,body,_=client.request('/admin/analytics/refresh',{'_csrf':client.token('/admin/analytics')})
        self.assertEqual(code,200); self.assertIn('ont été actualisés',body)
    def test_method_and_search_injection(self):
        client=Client(); self.assertEqual(client.request('/checkout')[0],405)
        code,body,_=client.request('/api/products?q='+urllib.parse.quote("' OR 1=1 --"))
        self.assertEqual(code,200); self.assertEqual(json.loads(body)['data'],[])
    def test_admin_product_crud_and_html_escaping(self):
        client=Client(); client.login_admin(); token=client.token('/admin/products/new')
        form=client.request('/admin/products/new')[1]
        category=re.search(r'<option value="(\d+)"',form).group(1)
        name='HTTP product '+uuid.uuid4().hex[:8]
        payload={'_csrf':token,'name':name,'description':'Description <script>alert(1)</script> complète','price':'42,35','stock':7,'category_id':category,'illustration':'bottle'}
        code,_,_=client.request('/admin/products',payload); self.assertEqual(code,200)
        products=json.loads(client.request('/api/products?q='+urllib.parse.quote(name))[1])['data']
        self.assertEqual(len(products),1); product=products[0]; self.assertEqual(product['price_cents'],4235)
        body=client.request('/products/'+str(product['id']))[1]
        self.assertIn('&lt;script&gt;',body); self.assertNotIn('<script>alert(1)</script>',body)
        payload['_csrf']=client.token('/admin/products/'+str(product['id'])+'/edit'); payload['price']='39.99'
        self.assertEqual(client.request('/admin/products/'+str(product['id']),payload)[0],200)
        product=json.loads(client.request('/api/products?q='+urllib.parse.quote(name))[1])['data'][0]
        self.assertEqual(product['price_cents'],3999)
        self.assertEqual(client.request('/admin/products/'+str(product['id'])+'/toggle',{'_csrf':payload['_csrf']})[0],200)
        self.assertEqual(json.loads(client.request('/api/products?q='+urllib.parse.quote(name))[1])['data'],[])

    def test_archived_cart_item_does_not_block_remaining_checkout(self):
        admin=Client(); admin.login_admin()
        hidden,_=self.fixture_product(admin); available,_=self.fixture_product(admin)
        client=Client(); client.register()
        client.add_product(hidden['id']); client.add_product(available['id'])
        old=client.checkout_payload()
        admin.archive_product(hidden['id'])
        code,body,_=client.request('/cart')
        self.assertEqual(code,200)
        self.assertIn('Panier : 1 articles',body)
        self.assertNotIn(hidden['name'],body)
        self.assertIn('retiré',body)
        self.assertEqual(client.request('/checkout',old)[0],409)
        code,body,url=client.request('/checkout',client.checkout_payload())
        self.assertEqual(code,200); self.assertRegex(url,r'/orders/\d+$')
        self.assertIn(available['name'],body); self.assertNotIn(hidden['name'],body)

    def test_stock_changed_checkout_is_atomic_and_cart_can_recover(self):
        admin=Client(); admin.login_admin()
        good,_=self.fixture_product(admin); scarce,payload=self.fixture_product(admin)
        client=Client(); client.register(); client.add_product(good['id']); client.add_product(scarce['id'])
        checkout=client.checkout_payload()
        payload['stock']=0; payload['_csrf']=admin.token(f"/admin/products/{scarce['id']}/edit")
        admin.request(f"/admin/products/{scarce['id']}",payload)
        code,body,url=client.request('/checkout',checkout)
        self.assertEqual(code,200); self.assertTrue(url.endswith('/cart')); self.assertIn('Stock insuffisant',body)
        self.assertEqual(client.products('?q='+urllib.parse.quote(good['name']))[0]['stock'],good['stock'])
        code,body,_=client.request('/cart/update',{'_csrf':client.token('/cart'),'product_id':scarce['id'],'quantity':0})
        self.assertEqual(code,200); self.assertNotIn(scarce['name'],body)
        self.assertRegex(client.request('/checkout',client.checkout_payload())[2],r'/orders/\d+$')

    def test_invalid_cart_quantities_and_stale_checkout_do_not_buy(self):
        admin=Client(); admin.login_admin(); product,_=self.fixture_product(admin,stock=5)
        client=Client(); client.register(); client.add_product(product['id'])
        checkout=client.checkout_payload()
        for quantity in (-1,11,6,'1.5','quantity'):
            with self.subTest(quantity=quantity):
                code,body,_=client.request('/cart/update',{'_csrf':client.token('/cart'),'product_id':product['id'],'quantity':quantity})
                self.assertEqual(code,200); self.assertIn('value="1"',body)
                self.assertIn('class="alert error"',body)
        client.request('/cart/update',{'_csrf':client.token('/cart'),'product_id':product['id'],'quantity':2})
        self.assertEqual(client.request('/checkout',checkout)[0],409)
        self.assertEqual(client.products('?q='+urllib.parse.quote(product['name']))[0]['stock'],5)
        client.request('/cart/update',{'_csrf':client.token('/cart'),'product_id':product['id'],'quantity':0})
        self.assertIn('Ton panier attend ses essentiels',client.request('/cart')[1])

    def test_registration_validation_session_rotation_and_role_escalation(self):
        client=Client(); email='validation-'+uuid.uuid4().hex+'@commercepulse.test'
        fields={'name':'<script>QA</script>','email':email,'password':'Valid-Long-2026!','password_confirmation':'Valid-Long-2026!','role':'admin'}
        for changes,message in [({'name':'A'},'Le nom doit contenir'),({'email':'not-an-email'},'adresse email valide'),
                                ({'password':'short'},'12 à 72'),({'password_confirmation':'Mismatch-2026!'},'doivent être identiques')]:
            with self.subTest(changes=changes):
                code,body,url=client.request('/register',dict(fields,**changes,_csrf=client.token('/register')))
                self.assertEqual(code,200); self.assertTrue(url.endswith('/register')); self.assertIn(message,body)
                self.assertNotIn('<script>QA</script>',body)
        before=next(cookie.value for cookie in client.jar if cookie.name=='commerce_pulse')
        code,body,url=client.request('/register',dict(fields,_csrf=client.token('/register')))
        self.assertEqual(code,200); self.assertTrue(url.endswith('/account')); self.assertIn('&lt;script&gt;QA&lt;/script&gt;',body)
        cookie=next(cookie for cookie in client.jar if cookie.name=='commerce_pulse')
        self.assertNotEqual(before,cookie.value); self.assertIn('HttpOnly',cookie._rest); self.assertEqual(cookie._rest['SameSite'],'Lax')
        self.assertEqual(client.request('/admin')[0],403)
        token=client.token('/account'); self.assertEqual(client.request('/logout',{'_csrf':token})[0],200)
        self.assertTrue(client.request('/account')[2].endswith('/login'))
        self.assertEqual(client.request('/logout',{'_csrf':token})[0],419)

    def test_duplicate_email_and_login_rate_limit(self):
        client=Client(); email=client.register()
        other=Client()
        code,body,url=other.request('/register',{'_csrf':other.token('/register'),'name':'Duplicate QA','email':email.upper(),
                                   'password':'Valid-Long-2026!','password_confirmation':'Valid-Long-2026!'})
        self.assertEqual(code,200); self.assertTrue(url.endswith('/register')); self.assertIn('déjà utilisée',body)
        email='missing-'+uuid.uuid4().hex+'@commercepulse.test'; token=other.token('/login')
        for _ in range(8):
            code,body,_=other.request('/login',{'_csrf':token,'email':email,'password':'Wrong-Pass-2026!'})
            self.assertEqual(code,200); self.assertIn('Email ou mot de passe incorrect',body)
        self.assertEqual(other.request('/login',{'_csrf':token,'email':email,'password':'Wrong-Pass-2026!'})[0],429)

    def test_invalid_admin_product_does_not_change_saved_data(self):
        admin=Client(); admin.login_admin(); product,fields=self.fixture_product(admin)
        route=f"/admin/products/{product['id']}"
        for changes in ({'price':'1e3'},{'price':'19.999'},{'stock':-1},{'category_id':999999},{'name':'A'}):
            with self.subTest(changes=changes):
                code,body,url=admin.request(route,dict(fields,**changes,_csrf=admin.token(route+'/edit')))
                self.assertEqual(code,200); self.assertTrue(url.endswith(route)); self.assertIn('class="alert error"',body)
                saved=admin.products('?q='+urllib.parse.quote(product['name']))[0]
                self.assertEqual((saved['price_cents'],saved['stock']),(product['price_cents'],product['stock']))

    def test_order_lifecycle_refuses_cancellation_after_shipping(self):
        admin=Client(); admin.login_admin(); product,_=self.fixture_product(admin)
        client=Client(); client.register(); client.add_product(product['id'])
        route=urllib.parse.urlparse(client.request('/checkout',client.checkout_payload())[2]).path
        status_route='/admin'+route+'/status'
        for status,label in [('processing','En préparation'),('shipped','Expédiée')]:
            code,body,_=admin.request(status_route,{'_csrf':admin.token(route),'status':status})
            self.assertEqual(code,200); self.assertIn(label,body)
        code,body,_=admin.request(status_route,{'_csrf':admin.token(route),'status':'cancelled'})
        self.assertEqual(code,200); self.assertIn('n’est pas autorisé',body); self.assertIn('Expédiée',body)
        self.assertEqual(client.products('?q='+urllib.parse.quote(product['name']))[0]['stock'],product['stock']-1)

    def test_private_paths_security_headers_and_malformed_csrf(self):
        client=Client()
        for route in ('/.env','/storage/local.sqlite','/tools/php/php.ini','/app/Core/Database.php','/%2e%2e/.env','/assets/%2e%2e/%2e%2e/.env'):
            with self.subTest(route=route): self.assertEqual(client.request(route)[0],404)
        self.assertEqual(client.request('/cart/add',{'_csrf[]':'wrong','product_id':1,'quantity':1})[0],419)
        client.request('/')
        self.assertIn("frame-ancestors 'none'",client.headers['Content-Security-Policy'])
        self.assertEqual(client.headers['X-Content-Type-Options'],'nosniff')
        self.assertEqual(client.headers['Cache-Control'],'no-store')
        client.register()
        self.assertEqual(client.request('/admin/products',{'_csrf':client.token('/account'),'name':'Forbidden'})[0],403)

    def test_catalogue_filters_pagination_and_html_search(self):
        client=Client()
        for sort in ('price_asc','price_desc','name','unknown'):
            with self.subTest(sort=sort):
                code,body,_=client.request('/api/products?sort='+sort+'&page=-1')
                self.assertEqual(code,200); result=json.loads(body); self.assertEqual(result['meta']['page'],1)
                self.assertFalse(any('password_hash' in item or 'customer_email' in item for item in result['data']))
                if sort.startswith('price_'):
                    prices=[item['price_cents'] for item in result['data']]
                    self.assertEqual(prices,sorted(prices,reverse=sort=='price_desc'))
        query=urllib.parse.quote('<script>alert("search")</script>')
        code,body,_=client.request('/?q='+query)
        self.assertEqual(code,200); self.assertNotIn('<script>alert("search")</script>',body)
        self.assertEqual(client.products('?category=999999'),[])

if __name__=='__main__':
    parser=argparse.ArgumentParser(); parser.add_argument('--base-url',default=BASE)
    args=parser.parse_args(); BASE=args.base_url.rstrip('/')
    if urllib.parse.urlparse(BASE).hostname not in ('localhost','127.0.0.1'): raise SystemExit('Only localhost test instances are allowed')
    unittest.main(argv=['http_smoke.py'])
