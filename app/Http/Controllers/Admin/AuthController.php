<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * 显示登录表单
     */
    public function showLoginForm()
    {
        if (auth('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.auth.login');
    }

    /**
     * 处理登录请求
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|min:6',
        ], [
            'login.required' => '用户名或邮箱不能为空',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少需要6位字符',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('login'));
        }

        $loginValue = $request->input('login');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // 判断是用邮箱还是用户名登录
        $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $credentials = [
            $loginField => $loginValue,
            'password' => $password
        ];

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $user = auth('admin')->user();
            
            // 检查用户状态
            if ($user->status !== 'active') {
                Auth::guard('admin')->logout();
                return back()->withErrors([
                    'login' => '账户已被停用，请联系管理员'
                ])->withInput($request->only('login'));
            }

            // 更新最后登录信息
            $user->updateLastLogin($request->ip());
            
            do_action('admin.user.logged_in', $user);

            $request->session()->regenerate();
            
            return redirect()
                ->intended(route('admin.dashboard'))
                ->with('success', '登录成功，欢迎回来！');
        }

        do_action('admin.user.login_failed', $loginValue);

        return back()->withErrors([
            'login' => '用户名/邮箱或密码错误'
        ])->withInput($request->only('login'));
    }

    /**
     * 处理退出请求
     */
    public function logout(Request $request)
    {
        $user = auth('admin')->user();
        
        do_action('admin.user.logging_out', $user);
        
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        do_action('admin.user.logged_out', $user);

        return redirect()
            ->route('admin.login')
            ->with('success', '已安全退出');
    }

    /**
     * 创建默认管理员账户（仅开发环境）
     */
    public function createDefaultAdmin(Request $request)
    {
        // 只在开发环境允许
        if (!app()->environment('local')) {
            abort(404);
        }

        // 检查是否已有管理员
        if (AdminUser::count() > 0) {
            return redirect()->route('admin.login')->with('info', '管理员账户已存在');
        }

        $admin = AdminUser::create([
            'name' => 'admin',
            'username' => 'admin',
            'email' => 'admin@gei5cms.local',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'is_super_admin' => true,
        ]);

        do_action('admin.user.created', $admin);

        return redirect()
            ->route('admin.login')
            ->with('success', '默认管理员账户创建成功！用户名：admin，邮箱：admin@gei5cms.local，密码：123456');
    }
}