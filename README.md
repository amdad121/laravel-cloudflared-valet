![Packagist version](https://flat.badgen.net/packagist/v/aerni/cloudflared/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/cloudflared) ![License](https://flat.badgen.net/github/license/aerni/laravel-cloudflared)

# Cloudflared for Laravel

An oppinionated package to help you create and manage Cloudflare Tunnels for your Laravel projects.

## Installation

Install the package using Composer:

```bash
composer require aerni/cloudflared
```

## Setup

1. Install [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads)
2. Run `cloudflared tunnel login` to authenticate the desired domain
3. Install [Laravel Herd](https://herd.laravel.com)
 
## Basic Usage

This package provides three Artisan commands to create, run, and delete project-specific tunnels.

Creates a new Cloudflare Tunnel for your project.
`php artisan cloudflared:install`

The package creates a `.cloudflared.yaml` file in your project root. This file is used to associate your project with the tunnel when running the tunnel.

Runs the Cloudflare Tunnel for your project.
`php artisan cloudflared:run`

Removes the Cloudflare Tunnel and cleans up associated resources.
`php artisan cloudflared:uninstall`

## How It Works

1. **Installation**: Creates a tunnel, DNS records, and local configuration
2. **Running**: Generates a tunnel config file and starts the `cloudflared` process
3. **Request Handling**: The service provider automatically sets the correct `app.url` for tunnel requests
4. **Cleanup**: Uninstall removes all traces of the tunnel and configuration

## Integration with Vite Plugin

This package works seamlessly with [vite-plugin-cloudflared](https://github.com/aerni/vite-plugin-cloudflared) to provide tunneled access to both your Laravel application and Vite's development server.

When you run `cloudflared:install`, it automatically creates DNS records for:
- Your main application: `hostname.yourdomain.com`
- Your Vite dev server: `vite-hostname.yourdomain.com`

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- **Michael Aerni** - [https://www.michaelaerni.ch](https://www.michaelaerni.ch)

## Support

For issues and questions, please use the [GitHub Issues](https://github.com/aerni/laravel-cloudflared/issues) page.
