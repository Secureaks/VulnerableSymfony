# Intended Vulnerable Symfony

> [!CAUTION]
> This application is intended to be vulnerable. Do not deploy it in a public environment.

This project is a vulnerable Symfony application intended to be used for security training purposes. The list of 
vulnerabilities is available below in the [List of vulnerabilities](#list-of-vulnerabilities) section.

![Screensho](resources/screenshot.png)

## Deployment

### Public Docker image

The easiest way to deploy the application is to use the public Docker image.

```bash
docker run -d --name VulenrableSymfony -p 8000:80 secureaks/vulnerablesymfony:latest
```

The application will be available at [http://localhost:8000](http://localhost:8000).

### Build the Docker image manually

You can build the Docker image manually, for example to customize the application.

First, clone the repository:

```bash
git clone https://github.com/Secureaks/VulnerableSymfony.git
cd VulnerableSymfony
```

Then build and run the Docker image:

```bash
docker buildx build --platform linux/amd64 -t secureaks/vulnerablesymfony:latest . --progress=plain
docker run -d --name vulenrablesymfony -p 8000:80 secureaks/vulnerablesymfony:latest
```

The application will be available at [http://localhost:8000](http://localhost:8000).

### Manual installation on Linux

If you prefer to install the application manually (without building a Docker image), you can follow the instructions below.

This guide is based on Ubuntu 22.04. For other distributions, you will need to adapt the commands.

#### Install the dependencies

```bash
apt install php-xml php-intl php-curl php-mbstring php-mysql php-sqlite3 php-zip php-gd php-imagick
```

#### Clone the repository

```bash
git clone https://github.com/Secureaks/VulnerableSymfony.git
cd VulnerableSymfony
```

#### Set the environment variables

```bash
cp .env .env.local
nano .env.local
```

#### Run the following commands

```bash
composer install
php bin/console doctrine:database:create # May be optional if the database already exists
php bin/console doctrine:schema:update --force
php bin/console blog:init 15
```

#### Start the dev server

```bash
symfony serve
```

Or:

```bash
php -S 0.0.0.0:8000 -t public
```

### Emails

If you want to try the password reset feature, you will need to configure the email sending. You need to uncomment 
the `MAILER_DSN` and `MAILER_SENDER` environment variables in the `.env.local` file, and set them up with your own values,
such as below:

```yaml
MAILER_DSN=smtps://user%40example.com:"password"@smtp.example.com:465
MAILER_SENDER=user@example.com
```

Then, you need to run the following command for the email to be sent:

```bash
php bin/console messenger:consume async
```

> [!WARNING]  
> This feature is not set up in the docker image, so if you want to test it, you will need to run the project manually.

## List of vulnerabilities

You can find below the list of vulnerabilities available in the application. The source code is also documented to allow
you to understand how the vulnerabilities are implemented.

- Server Side Request Forgery and Remote Code Execution in the referer header on `/` and `/post/{post}`
- Stored XSS on comment parameter on `/post/{post}/comment`
- Reflected XSS and SQL Injection on search parameter on `/search`
- Local File Inclusion on `p` parameter on `legal/content`
- SQL Injection on `email` parameter on `/login`
- User enumeration on `/register`
- Missing right control on `/user/role/{user}`
- Missing right control on `/user/delete/{user}`
- Missing right control on `/user/password/{user}`
- Missing right control leading to privilege escalation on `/user/email/{user}`
- File Upload - No extension check on `/user/avatar/{user}`
- Server Side Request Forgery on the `url` parameter on `/user/avatar/url/{user}`
- Missing right control on `/user/avatar/delete/{user}`
- Command injection on the extension of the uploaded file on `/user/avatar/resize/{user}`
- Server Side Template Injection on `/user/about`
- Sensitive endpoint intended to be used through SSRF on `/local`
- Technical information disclosure on `/info.php`
- Directory listing if using the option `Options +Indexes` on the vhost configuration
- Mass assignment on `/user/edit/` leading to privilege escalation
- Mass assignment on `/reset` leading to account takeover (needs mail configuration to be set up)

## Credits

This project is provided by [Secureaks](https://secureaks.com).

## Contributing

If you want to contribute to this project, fill free to open an issue or a pull request with your changes.

## License

This project is licensed under the ATTRIBUTION-NONCOMMERCIAL-SHAREALIKE 4.0 INTERNATIONAL (CC BY-NC-SA 4.0) license. 

You are free to:

- Share — copy and redistribute the material in any medium or format
- Adapt — remix, transform, and build upon the material

The licensor cannot revoke these freedoms as long as you follow the license terms.

Under the following terms:

- Attribution — You must give appropriate credit , provide a link to the license, and indicate if changes were made . You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
- NonCommercial — You may not use the material for commercial purposes .
- ShareAlike — If you remix, transform, or build upon the material, you must distribute your contributions under the same license as the original.

No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.

See the [LICENSE](LICENSE) file for details.

