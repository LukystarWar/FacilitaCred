<?php

class ErrorHandler {

    public static function register() {
        error_reporting(E_ALL);

        if (APP_ENV === 'production') {
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', __DIR__ . '/../logs/error.log');
        } else {
            ini_set('display_errors', 1);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $message = "Error [$errno]: $errstr in $errfile on line $errline";

        if (APP_ENV === 'production') {
            error_log($message);
            self::showErrorPage('Ocorreu um erro. Tente novamente mais tarde.');
        } else {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }

        return true;
    }

    public static function handleException($exception) {
        $message = "Uncaught Exception: " . $exception->getMessage() .
                   " in " . $exception->getFile() .
                   " on line " . $exception->getLine();

        error_log($message);
        error_log($exception->getTraceAsString());

        if (APP_ENV === 'production') {
            self::showErrorPage('Ocorreu um erro inesperado. Tente novamente mais tarde.');
        } else {
            self::showErrorPage(
                $exception->getMessage() . '<br><br>' .
                '<strong>File:</strong> ' . $exception->getFile() . '<br>' .
                '<strong>Line:</strong> ' . $exception->getLine() . '<br><br>' .
                '<pre>' . $exception->getTraceAsString() . '</pre>'
            );
        }
    }

    public static function handleShutdown() {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            error_log($message);

            if (APP_ENV === 'production') {
                self::showErrorPage('Ocorreu um erro crítico. Entre em contato com o suporte.');
            }
        }
    }

    private static function showErrorPage($message) {
        if (headers_sent()) {
            echo "<div style='padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 4px; margin: 20px;'>";
            echo "<strong>Erro:</strong> " . $message;
            echo "</div>";
            return;
        }

        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro - <?= APP_NAME ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f3f4f6;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    padding: 40px;
                    max-width: 600px;
                    text-align: center;
                }
                .error-icon {
                    font-size: 64px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #ef4444;
                    font-size: 24px;
                    margin-bottom: 16px;
                }
                p {
                    color: #6b7280;
                    line-height: 1.6;
                    margin-bottom: 24px;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background: #2563eb;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 500;
                    transition: background 0.2s;
                }
                .btn:hover {
                    background: #1e40af;
                }
                pre {
                    text-align: left;
                    background: #f9fafb;
                    padding: 16px;
                    border-radius: 8px;
                    overflow-x: auto;
                    margin-top: 20px;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1>Ops! Algo deu errado</h1>
                <p><?= $message ?></p>
                <a href="<?= BASE_URL ?>/dashboard" class="btn">Voltar ao Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
