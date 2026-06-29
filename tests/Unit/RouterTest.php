<?php
declare(strict_types=1);

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Router;

final class RouterTestMw implements Middleware
{
    public function __construct(private string $label)
    {
    }

    public function handle(Request $request): void
    {
        $GLOBALS['mw_order'][] = $this->label;
    }
}

test('router extracts params and runs middleware in order', function () {
    $GLOBALS['mw_order'] = [];
    $router = new Router();

    $router->group([new RouterTestMw('A')], function (Router $r): void {
        $r->get('/dog/{id}', function (string $id): string {
            $GLOBALS['mw_order'][] = 'handler';
            return 'dog:' . $id;
        }, [new RouterTestMw('B')]);
    });

    $result = $router->dispatch('GET', '/dog/42?ignored=1');
    assert_same('dog:42', $result);
    assert_same(['A', 'B', 'handler'], $GLOBALS['mw_order']);
});

test('router supports POST _method override', function () {
    $_POST['_method'] = 'DELETE';
    $router = new Router();
    $router->delete('/x/{id}', fn (string $id): string => 'deleted:' . $id);

    $result = $router->dispatch('POST', '/x/9');
    assert_same('deleted:9', $result);
    unset($_POST['_method']);
});
