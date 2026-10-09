<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Database as DB,Session,Response,HttpException};
final class AuthController
{
    public function loginForm(): void { Response::view('auth',['title'=>'Connexion','register'=>false]); }
    public function registerForm(): void { Response::view('auth',['title'=>'Créer un compte','register'=>true]); }
    public function login(): void
    {
        $email=strtolower(trim((string)($_POST['email'] ?? ''))); $password=(string)($_POST['password'] ?? '');
        $scope=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? 'local').'|'.$email);
        $since=gmdate('Y-m-d H:i:s',time()-900);
        DB::query('DELETE FROM login_attempts WHERE attempted_at<?',[gmdate('Y-m-d H:i:s',time()-86400)]);
        if ((int)DB::query('SELECT COUNT(*) FROM login_attempts WHERE scope_key=? AND attempted_at>=?',[$scope,$since])->fetchColumn()>=8) throw new HttpException(429,'Trop de tentatives. Réessaie dans 15 minutes.');
        $user=DB::query('SELECT * FROM users WHERE email=?',[$email])->fetch();
        if (!$user || !password_verify($password,$user['password_hash'])) {
            DB::query('INSERT INTO login_attempts (scope_key,attempted_at) VALUES (?,?)',[$scope,gmdate('Y-m-d H:i:s')]);
            Response::view('auth',['title'=>'Connexion','register'=>false,'error'=>'Email ou mot de passe incorrect.','email'=>$email]); return;
        }
        DB::query('DELETE FROM login_attempts WHERE scope_key=?',[$scope]); Session::login((int)$user['id']);
        Response::redirect($user['role']==='admin' ? '/admin' : '/account');
    }
    public function register(): void
    {
        $name=trim((string)($_POST['name'] ?? '')); $email=strtolower(trim((string)($_POST['email'] ?? ''))); $password=(string)($_POST['password'] ?? '');
        $error=null;
        if (strlen($name)<2 || strlen($name)>100) $error='Le nom doit contenir entre 2 et 100 caractères.';
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) $error='Renseigne une adresse email valide.';
        elseif (strlen($password)<12 || strlen($password)>72) $error='Choisis un mot de passe de 12 à 72 octets.';
        elseif ($password!==($_POST['password_confirmation'] ?? '')) $error='Les deux mots de passe doivent être identiques.';
        if ($error) { Response::view('auth',compact('error','name','email')+['title'=>'Créer un compte','register'=>true]); return; }
        try { DB::query('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'customer',gmdate('Y-m-d H:i:s')]); }
        catch (\PDOException $e) {
            if ($e->getCode()!=='23000') throw $e;
            Response::view('auth',['title'=>'Créer un compte','register'=>true,'error'=>'Cette adresse email est déjà utilisée.','name'=>$name,'email'=>$email]); return;
        }
        Session::login((int)DB::connect()->lastInsertId()); Session::flash('Ton compte est prêt. Bienvenue chez Commerce Pulse.'); Response::redirect('/account');
    }
    public function logout(): void { Session::logout(); Response::redirect('/'); }
}
