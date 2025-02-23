<?php
declare(strict_types=1);

use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\AuthClient;

define('SERVER_URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);
const VENDOR_AUTOLOAD_PATH = __DIR__ . '/../vendor/autoload.php';
const ROUTE_DIR = __DIR__ . '/../../html-examples';

final class Examples
{
    /**
     * @var callable
     */
    private static $bootstrap;
    private static string $title = '';
    private static string $description = '';
    private static array $routes = [];
    private static string|null $currentPagePath = null;
    private static string|null $currentRoute = null;

    public static function title(string $title): void
    {
        self::$title = $title;
    }

    /**
     * A semantic feature to show the description of the example at the header of the file,
     * and allow it to be printed later using the {@see showDescription} function.
     *
     * @param string $description
     * @return void
     */
    public static function description(string $description): void
    {
        self::$description = trim($description);
    }

    /**
     * This function is called before the route is executed.
     * In a real world scenario you would put this code somewhere globally, in a middleware or in a base controller.
     *
     * In the examples this code is executed before the route is executed.
     *
     * @param callable(): (array|null) $callback The function to execute before the route is executed.
     *                                           If an array is returned, the array will be passed to the route callback as arguments.
     * @return void
     */
    public static function bootstrap(callable $callback): void
    {
        self::$bootstrap = $callback;
    }

    /**
     * This function is used to bootstrap the stateful authentication.
     * It should keep the examples that do not focus on authentication clean and simple.
     * It automatically provides the "/callback" route and the "/login" route.
     * If you want to learn more about what this function does, take a look at the "stateful-auth" example.
     * @return void
     */
    public static function bootstrapStatefulAuth(): void
    {
        self::$bootstrap = static function () {
            self::includeComposerAutoload();

            $client = new AuthClient(
                redirectUrl: self::getPageUrl() . '/callback',
                publicKeycloakUrl: getenv('PUBLIC_KEYCLOAK_URL'),
                realm: getenv('REALM'),
                clientId: getenv('CLIENT_ID'),
                clientSecret: getenv('CLIENT_SECRET'),
                internalKeycloakUrl: empty(getenv('INTERNAL_KEYCLOAK_URL')) ? null : getenv('INTERNAL_KEYCLOAK_URL'),
            );

            session_start();

            $auth = $client->statefulAuth();
            $auth->authenticate();

            return [$client, $auth];
        };

        self::route('GET', '/callback', static function ($_, StatefulAuth $auth) {
            $auth->handleCallback(
                onHandled: fn() => Examples::getPageUrl(),
            );
        });

        self::route('GET', '/login', static function ($_, StatefulAuth $auth) {
            $auth->login();
        });
    }

    /**
     * Register a new route.
     * In a real world scenario your framework will do this for you.
     *
     * @param string|array $method The HTTP method to listen to. E.g. "GET", "POST", "PUT", "DELETE". If you want to listen to multiple methods, pass an array.
     * @param string $route The route to listen to. E.g. "/login", "/logout", "/callback".
     * @param callable $callback The function to execute when the route is hit.
     * @return void
     */
    public static function route(string|array $method, string $route, callable $callback): void
    {
        $method = is_array($method) ? $method : [$method];
        foreach ($method as $m) {
            self::$routes[$m][ltrim($route, ' /')] = $callback;
        }
    }

    /**
     * This function is used to show a back link to the index page.
     * @return void
     */
    public static function showBackLink(): void
    {
        echo '<hr>';
        echo '<a href="/">Back to index</a>';
    }

    /**
     * This function is used to show the description of the example.
     * @return void
     */
    public static function showDescription(): void
    {
        echo '<h1>' . self::getTitle() . '</h1>';
        echo '<p>' . self::getDescription() . '</p>';
        echo '<hr>';
    }

    /**
     * Returns the title of the currently loaded example.
     * @return string
     */
    public static function getTitle(): string
    {
        $title = strip_tags(self::$title);

        if (empty($title)) {
            if (self::$currentPagePath === null) {
                return 'Examples';
            }

            return self::fileNameToHuman(self::$currentPagePath);
        }

        return $title;
    }

    /**
     * Returns the description of the currently loaded example.
     * @param bool $shortened If true, the description will be shortened to 300 characters.
     * @return string|null
     */
    public static function getDescription(bool $shortened = false): string|null
    {
        $desc = self::$description;

        if ($shortened) {
            $desc = strip_tags($desc);
            $descShortened = substr($desc, 0, 300);
            if ($descShortened !== $desc) {
                $desc = $descShortened . '...';
            }
        }
        $desc = nl2br($desc);

        return $desc === '' ? null : $desc;
    }

    /**
     * Returns the URL of the currently loaded example.
     * This is always the BASE url of the current example, without any query parameters and without a trailing slash.
     * e.g. "http://localhost:8080/stateful-auth"
     * @return string
     */
    public static function getPageUrl(): string
    {
        return SERVER_URL . '/' . self::$currentPagePath;
    }

    /**
     * Similar to {@see getPageUrl}, but includes the current route.
     * e.g "http://localhost:8080/stateful-auth/login"
     * @return string
     */
    public static function getRouteUrl(): string
    {
        $route = self::$currentRoute;

        if (empty($route)) {
            return self::getPageUrl();
        }

        return self::getPageUrl() . '/' . $route;
    }

    /**
     * This function is used to include the composer autoload file.
     * @return void
     */
    public static function includeComposerAutoload(): void
    {
        require_once VENDOR_AUTOLOAD_PATH;
    }

    /**
     * NO TOUCHY! This function is the main entry point for the examples.
     * @return void
     */
    public static function handle(): void
    {
        $givenRoute = empty($_GET['route']) ? 'index' : $_GET['route'];
        $pagePath = strtok($givenRoute, '/');
        $route = strtok('');
        $route = $route === false ? '' : $route;

        if ($pagePath === 'index') {
            self::showIndex();
        }

        if (!self::loadFileByPagePath($pagePath)) {
            self::show404();
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (isset(self::$routes[$method][$route])) {
            self::$currentRoute = $route;
            $routeArgs = [];
            if (self::$bootstrap) {
                $_routeArgs = (self::$bootstrap)();
                if (is_array($_routeArgs)) {
                    $routeArgs = $_routeArgs;
                }
            }

            self::$routes[$method][$route](...$routeArgs);
            exit();
        }

        self::show404();
    }

    private static function loadFileByPagePath(string $pagePath): bool
    {
        self::$title = '';
        self::$description = '';
        self::$currentPagePath = null;

        $available = self::findAvailableFiles();
        if (isset($available[$pagePath])) {
            self::$currentPagePath = $pagePath;
            require $available[$pagePath];
            return true;
        }

        return false;
    }

    private static function findAvailableFiles(): array
    {
        $routes = [];
        foreach (scandir(ROUTE_DIR, SCANDIR_SORT_ASCENDING) as $file) {
            $routeFile = ROUTE_DIR . '/' . $file . '/index.php';
            if (is_file($routeFile)) {
                $routes[pathinfo($file, PATHINFO_FILENAME)] = $routeFile;
            }
        }
        return $routes;
    }

    private static function show404(): never
    {
        header('HTTP/1.0 404 Not Found');
        echo '<h1>404 Not Found</h1>';
        echo '<p>The requested route was not found.</p>';
        echo '<a href="/">Back to index</a>';
        exit();
    }

    private static function showIndex(): never
    {
        echo '<h1>Examples</h1>';
        echo '<ul>';
        foreach (self::findAvailableFiles() as $pagePath => $file) {
            self::loadFileByPagePath($pagePath);

            echo '<li>';
            echo '<a href="/' . $pagePath . '">' . self::getTitle() . '</a>';
            $desc = self::getDescription(true);
            if ($desc !== null) {
                echo '<br><small>' . $desc . '</small>';
            }
            echo '</li>';
        }
        echo '</ul>';
        exit();
    }

    private static function fileNameToHuman(string $fileName): string
    {
        return ucfirst(str_replace('-', ' ', $fileName));
    }
}

Examples::handle();
