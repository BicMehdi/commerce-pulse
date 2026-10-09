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
    def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(self,path,data=None):
        request=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
        try: response=self.opener.open(request,timeout=30)
        except urllib.error.HTTPError as error: response=error
        return response.status,response.read().decode(),response.geturl()
    def token(self,path):
        code,body,_=self.request(path); assert code==200,(code,body[:100])
        return re.search(r'name="_csrf" value="([a-f0-9]+)"',body).group(1)
    def register(self):
        email=f"http-{uuid.uuid4().hex}@commercepulse.test"
        code,body,_=self.request('/register',{'_csrf':self.token('/register'),'name':'HTTP Test','email':email,'password':'Http-Test-2026!','password_confirmation':'Http-Test-2026!'})
        assert code==200 and 'Mon espace' in body
    def login_admin(self):
        code,body,_=self.request('/login',{'_csrf':self.token('/login'),'email':'admin@commercepulse.test','password':os.getenv('DEMO_PASSWORD','Demo-Pulse-2026!')})
        assert code==200 and 'Vue d’ensemble' in body

class HttpTests(unittest.TestCase):
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

if __name__=='__main__':
    parser=argparse.ArgumentParser(); parser.add_argument('--base-url',default=BASE)
    args=parser.parse_args(); BASE=args.base_url.rstrip('/')
    if urllib.parse.urlparse(BASE).hostname not in ('localhost','127.0.0.1'): raise SystemExit('Only localhost test instances are allowed')
    unittest.main(argv=['http_smoke.py'])
