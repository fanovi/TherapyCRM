<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Grafico Manager API - Documentazione</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Ottieni la base URL dinamicamente
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/swagger\/?$/, '');
            const jsonUrl = baseUrl + '/swagger/json';
            
            const ui = SwaggerUIBundle({
                url: jsonUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                tryItOutEnabled: true,
                filter: true,
                persistAuthorization: true,
                requestInterceptor: (request) => {
                    console.log('Request:', request);
                    // Log del token per debugging
                    if (request.headers && request.headers.Authorization) {
                        console.log('Authorization header presente');
                    }
                    return request;
                },
                responseInterceptor: (response) => {
                    console.log('Response:', response);
                    return response;
                },
                onComplete: function() {
                    console.log('Swagger UI caricato completamente');
                    
                    // Aggiungi istruzioni per l'autenticazione
                    setTimeout(() => {
                        const authSection = document.querySelector('.auth-wrapper');
                        if (authSection) {
                            const instructions = document.createElement('div');
                            instructions.style.cssText = 'background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin: 10px 0; font-size: 14px;';
                            instructions.innerHTML = `
                                <h4 style="margin-top: 0; color: #495057;">🔐 Come autenticarsi:</h4>
                                <ol style="margin: 10px 0;">
                                    <li><strong>Fai login</strong> usando l'endpoint <code>POST /auth/login</code> con email e password</li>
                                    <li><strong>Copia il token</strong> dalla risposta (campo <code>access_token</code>)</li>
                                    <li><strong>Clicca "Authorize"</strong> in alto a destra</li>
                                    <li><strong>Incolla il token</strong> nel campo (solo il token, senza "Bearer")</li>
                                    <li><strong>Clicca "Authorize"</strong> per salvare</li>
                                </ol>
                                <p style="margin-bottom: 0;"><em>💡 Credenziali di test: <code>paziente@test.it</code> / <code>12345678</code></em></p>
                            `;
                            
                            const topbar = document.querySelector('.topbar');
                            if (topbar) {
                                topbar.appendChild(instructions);
                            }
                        }
                    }, 1000);
                }
            });
        };
    </script>
</body>
</html> 