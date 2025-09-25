<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminLteMenuMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Aplicar a todas las vistas de AdminLTE
        view()->composer([
            'vendor.adminlte.*',
            'adminlte::*'
        ], function ($view) {
            $data = $view->getData();
            if (!isset($data['item'])) {
                $view->with('item', $this->getDefaultMenuItem());
            }
        });

        // También aplicar a vistas específicas que necesitan $item
        view()->composer([
            'vendor.adminlte.partials.navbar.menu-item-dropdown-menu',
            'vendor.adminlte.partials.sidebar.menu-item-treeview-menu'
        ], function ($view) {
            $data = $view->getData();
            if (!isset($data['item'])) {
                $view->with('item', $this->getDefaultMenuItem());
            }
        });

        return $next($request);
    }

    /**
     * Get default menu item structure
     */
    private function getDefaultMenuItem()
    {
        return [
            'href' => '#',
            'method' => 'GET',
            'text' => 'Buscar...',
            'input_name' => 'search',
            'class' => '',
            'icon' => 'fas fa-search',
            'icon_color' => '',
            'label' => '',
            'label_color' => 'primary',
            'target' => '',
            'id' => '',
            'data-compiled' => '',
            'submenu' => [],
            'submenu_class' => '',
            'shift' => ''
        ];
    }
}
