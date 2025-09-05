<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

abstract class BaseAdminController extends Controller
{
    protected string $model = '';
    protected string $title = '';
    protected string $route = '';
    protected array $columns = [];
    protected array $fields = [];
    protected array $permissions = [];

    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('admin.permission');
        
        // 设置全局视图数据
        View::share('admin_title', $this->title);
        View::share('admin_route', $this->route);
    }

    public function index(Request $request)
    {
        $this->checkPermission('view');

        $query = $this->model::query();
        
        // 应用筛选
        $this->applyFilters($query, $request);
        
        // 应用搜索
        if ($request->filled('search')) {
            $this->applySearch($query, $request->get('search'));
        }
        
        // 应用排序
        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->get('per_page', 25), 100);
        $items = $query->paginate($perPage);

        do_action('admin.list.loaded', $this->route, $items);

        return view('admin.crud.list', [
            'items' => $items,
            'columns' => $this->getColumns(),
            'title' => $this->title,
            'route' => $this->route,
            'can_create' => $this->hasPermission('create'),
            'can_edit' => $this->hasPermission('edit'),
            'can_delete' => $this->hasPermission('delete'),
            'search_query' => $request->get('search', ''),
            'filters' => $this->getFilters($request),
        ]);
    }

    public function create()
    {
        $this->checkPermission('create');

        $item = new $this->model();
        $fields = $this->getFields('create');

        do_action('admin.form.create', $this->route);

        return view('admin.crud.form', [
            'item' => $item,
            'fields' => $fields,
            'title' => "创建 {$this->title}",
            'route' => $this->route,
            'form_action' => route("admin.{$this->route}.store"),
            'form_method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $data = $this->validateRequest($request, 'create');
        
        do_action('admin.item.creating', $this->route, $data);

        $item = $this->model::create($data);

        do_action('admin.item.created', $this->route, $item);

        return redirect()
            ->route("admin.{$this->route}.index")
            ->with('success', "{$this->title} 创建成功");
    }

    public function show(int $id)
    {
        $this->checkPermission('view');

        $item = $this->model::findOrFail($id);
        
        do_action('admin.item.showing', $this->route, $item);

        return view('admin.crud.show', [
            'item' => $item,
            'columns' => $this->getColumns(),
            'title' => $this->title,
            'route' => $this->route,
            'can_edit' => $this->hasPermission('edit'),
            'can_delete' => $this->hasPermission('delete'),
        ]);
    }

    public function edit(int $id)
    {
        $this->checkPermission('edit');

        $item = $this->model::findOrFail($id);
        $fields = $this->getFields('edit');

        do_action('admin.form.edit', $this->route, $item);

        return view('admin.crud.form', [
            'item' => $item,
            'fields' => $fields,
            'title' => "编辑 {$this->title}",
            'route' => $this->route,
            'form_action' => route("admin.{$this->route}.update", $item->id),
            'form_method' => 'PUT',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->checkPermission('edit');

        $item = $this->model::findOrFail($id);
        $data = $this->validateRequest($request, 'edit');

        do_action('admin.item.updating', $this->route, $item, $data);

        $item->update($data);

        do_action('admin.item.updated', $this->route, $item);

        return redirect()
            ->route("admin.{$this->route}.index")
            ->with('success', "{$this->title} 更新成功");
    }

    public function destroy(int $id)
    {
        $this->checkPermission('delete');

        $item = $this->model::findOrFail($id);

        do_action('admin.item.deleting', $this->route, $item);

        $item->delete();

        do_action('admin.item.deleted', $this->route, $item);

        return redirect()
            ->route("admin.{$this->route}.index")
            ->with('success', "{$this->title} 删除成功");
    }

    public function bulkAction(Request $request)
    {
        $this->checkPermission('bulk');

        $action = $request->get('action');
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            return back()->with('error', '请选择要操作的项目');
        }

        $items = $this->model::whereIn('id', $ids)->get();

        do_action('admin.bulk.action', $this->route, $action, $items);

        switch ($action) {
            case 'delete':
                $this->checkPermission('delete');
                $this->model::whereIn('id', $ids)->delete();
                return back()->with('success', "已删除 " . count($ids) . " 个项目");

            case 'activate':
                $this->model::whereIn('id', $ids)->update(['status' => 'active']);
                return back()->with('success', "已激活 " . count($ids) . " 个项目");

            case 'deactivate':
                $this->model::whereIn('id', $ids)->update(['status' => 'inactive']);
                return back()->with('success', "已停用 " . count($ids) . " 个项目");

            default:
                return back()->with('error', '未知操作');
        }
    }

    protected function getColumns(): array
    {
        return apply_filters("admin.{$this->route}.columns", $this->columns);
    }

    protected function getFields(string $operation = 'create'): array
    {
        return apply_filters("admin.{$this->route}.fields.{$operation}", $this->fields);
    }

    protected function getFilters(Request $request): array
    {
        return [];
    }

    protected function applyFilters($query, Request $request): void
    {
        // 子类实现具体的筛选逻辑
    }

    protected function applySearch($query, string $search): void
    {
        // 子类实现具体的搜索逻辑
    }

    protected function validateRequest(Request $request, string $operation): array
    {
        $rules = $this->getValidationRules($operation);
        return $request->validate($rules);
    }

    protected function getValidationRules(string $operation): array
    {
        // 子类实现具体的验证规则
        return [];
    }

    protected function checkPermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            abort(403, '没有权限执行此操作');
        }
    }

    protected function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true;
        }

        $permissionKey = "{$this->route}.{$permission}";
        return in_array($permissionKey, $this->permissions) && 
               auth('admin')->user()->hasPermission($permissionKey);
    }
}