# Intended Vulnerable Symfony

This is a vulnerable Symfony application. It is intended to be used for security training purposes.

## Installation

### Clone the repository

```bash
git clone https://github.com/Secureaks/VulnerableSymfony.git
cd VulnerableSymfony
```

## Usage in development

### Deploy the application

```bash
bash deploy.sh $(whoami) dev
```

### Start the dev server

```bash
symfony serve
```

Or

```bash
php -S 0.0.0.0:8000 -t public
```

## Usage in production

### Deploy the application

```bash
bash deploy.sh <web_user> prod
```

Exemple :

```bash
bash deploy.sh www-data prod
```

### Setup the web server vhost (example with Apache)

```bash
nano /etc/apache2/sites-available/vulnerable-symfony.conf
```

```apache
<VirtualHost *:80>
    ServerName vulnerable-symfony.local
    DocumentRoot /var/www/VulnerableSymfony/public

    <Directory /var/www/VulnerableSymfony/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        Options +Indexes
    </Directory>
</VirtualHost>
```

```bash
a2ensite vulnerable-symfony
systemctl reload apache2
```

## List of vulnerabilities

- Server Side Request Forgery and Remote Code Execution in the referer header on `/` and `/post/{post}`
- Stored XSS on comment parameter on `/post/{post}/comment`
- Reflected XSS and SQL Injection on search parameter on `/search`
- Local File Inclusion on `p` parameter on `legal/content`
- SQL Injection on `email` parameter on `/login`
- User enumeration on `/register`
- Missing right control on `/user/role/{user}`
- Missing right control on `/user/delete/{user}`
- Missing right control and privilege escalation on `/user/password/{user}`
- Missing right control leading to privilege escalation on `/user/email/{user}`
- File Upload - No extension check on `/user/avatar/{user}`
- Server Side Request Forgery on the `url` parameter on `/user/avatar/url/{user}`
- Missing right control on `/user/avatar/delete/{user}`
- Command injection on the extension of the uploaded file on `/user/avatar/resize/{user}`
- Server Side Template Injection on `/user/about`
- Sensitive endpoint intended to be used through SSRF on `/local`
- Technical information disclosure on `/info.php`
- Directory listing if using the option `Options +Indexes` on the vhost configuration