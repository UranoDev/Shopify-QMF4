<?php
// Configuración inicial de la app
$api_version = '2023-10';
$limit = 10;

// Configuración de App Bridge
$shop = $_GET['shop'];
$host = $_GET['host'] ?? '';
$api_key = 'bb1676b724383d143f714c00ca67e2e4'; // Reemplazar con tu API key qmf4
$api_key = 'a1612c6cb5cb2242a13299267cf896ec'; // Reemplazar con tu API key qmf priv
$api_key = getenv('SHOPIFY_API_KEY');


// Función para extraer page_info
function extract_page_info($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    return $params['page_info'] ?? '';
}

// Resto del código PHP igual que antes...
?>

    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Órdenes Recientes</title>
        <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-utils@3"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-react@3"></script>
        <link href="https://unpkg.com/@shopify/polaris@12.5.0/build/esm/styles.css" rel="stylesheet">
        <meta name="shopify-api-key" content="<?php echo $api_key; ?>" />
        <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    </head>
    <body>
    <div id="app">
        <div class="app-wrapper">
            <!-- Menú de navegación lateral -->
            <div class="app-nav">
                <div class="Polaris-Frame">
                    <div class="Polaris-Frame__Navigation">
                        <nav class="Polaris-Navigation">
                            <div class="Polaris-Navigation__Section">
                                <ul class="Polaris-Navigation__List">
                                    <li class="Polaris-Navigation__ListItem">
                                        <a class="Polaris-Navigation__Item"
                                           href="<?php echo getAppUrl('preferences.php'); ?>"
                                           data-polaris-unstyled="true">
                                            <span class="Polaris-Navigation__Icon">
                                                <span class="Polaris-Icon">
                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                        <path d="M10 2l8 8h-3v8H5v-8H2l8-8z"></path>
                                                    </svg>
                                                </span>
                                            </span>
                                            <span class="Polaris-Navigation__Text">Inicio</span>
                                        </a>
                                    </li>
                                    <li class="Polaris-Navigation__ListItem">
                                        <a class="Polaris-Navigation__Item Polaris-Navigation--selected"
                                           href="<?php echo getAppUrl('orders.php'); ?>"
                                           data-polaris-unstyled="true">
                                            <span class="Polaris-Navigation__Icon">
                                                <span class="Polaris-Icon">
                                                    <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg">
                                                        <path d="M17 5h-4V3h4v2zm0 4h-4V7h4v2zm0 4h-4v-2h4v2zm0 4h-4v-2h4v2zM3 19h12V5H3v14zM1 3h16v16H1V3z"></path>
                                                    </svg>
                                                </span>
                                            </span>
                                            <span class="Polaris-Navigation__Text">Órdenes</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="app-content">
                <div class="Polaris-Page">
                    <div class="Polaris-Page__Header">
                        <div class="Polaris-Page__Title">
                            <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge">Órdenes Recientes</h1>
                        </div>
                    </div>

                    <!-- Resto del contenido de órdenes igual que antes -->
                    <div class="Polaris-Page__Content">
                        <!-- Tu tabla de órdenes aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicializar App Bridge
        const app = window.appBridge.default({
            apiKey: '<?php echo $api_key; ?>',
            host: '<?php echo $host; ?>',
            shop: '<?php echo $shop; ?>'
        });

        // Configurar título de la página
        const titleBarOptions = {
            title: 'Gestión de Órdenes',
            buttons: {
                primary: {
                    content: 'Regresar',
                    onClick: () => {
                        window.location.href = '<?php echo getAppUrl('preferences.php'); ?>';
                    }
                }
            }
        };

        const TitleBar = window.appBridge.TitleBar;
        TitleBar.create(app, titleBarOptions);

        // Manejar navegación
        const Redirect = window.appBridge.Redirect;
        document.querySelectorAll('.Polaris-Navigation__Item').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const redirect = Redirect.create(app);
                redirect.dispatch(Redirect.Action.APP, link.getAttribute('href'));
            });
        });
    </script>

    <style>
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .app-nav {
            width: 240px;
            background: #f4f6f8;
            border-right: 1px solid #dfe3e8;
        }

        .app-content {
            flex: 1;
            padding: 20px;
            background: white;
        }
    </style>
    </body>
    </html>

<?php
// Función para generar URLs de la app
function getAppUrl($path) {
    return "https://qmf4.urano.dev/$path?shop={$_GET['shop']}&host={$_GET['host']}";
}