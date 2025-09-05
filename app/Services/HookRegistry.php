<?php

namespace App\Services;

class HookRegistry
{
    protected static array $predefinedHooks = [
        // 系统启动和关闭钩子
        'system' => [
            'system.boot' => '系统启动时触发',
            'system.ready' => '系统完全准备就绪时触发',
            'system.shutdown' => '系统关闭时触发',
            'system.maintenance.start' => '维护模式开始',
            'system.maintenance.end' => '维护模式结束',
            'system.update.before' => '系统更新前',
            'system.update.after' => '系统更新后',
            'system.backup.start' => '系统备份开始',
            'system.backup.end' => '系统备份完成',
            'system.error' => '系统错误时触发',
        ],

        // 用户管理钩子
        'user' => [
            'user.register.before' => '用户注册前',
            'user.register.after' => '用户注册后',
            'user.login.before' => '用户登录前',
            'user.login.after' => '用户登录后',
            'user.logout.before' => '用户登出前',
            'user.logout.after' => '用户登出后',
            'user.profile.update.before' => '用户资料更新前',
            'user.profile.update.after' => '用户资料更新后',
            'user.password.change.before' => '用户密码修改前',
            'user.password.change.after' => '用户密码修改后',
            'user.delete.before' => '用户删除前',
            'user.delete.after' => '用户删除后',
            'user.activate' => '用户激活',
            'user.deactivate' => '用户停用',
            'user.role.change' => '用户角色变更',
            'user.permission.change' => '用户权限变更',
        ],

        // 内容管理钩子
        'content' => [
            'content.create.before' => '内容创建前',
            'content.create.after' => '内容创建后',
            'content.update.before' => '内容更新前',
            'content.update.after' => '内容更新后',
            'content.delete.before' => '内容删除前',
            'content.delete.after' => '内容删除后',
            'content.publish.before' => '内容发布前',
            'content.publish.after' => '内容发布后',
            'content.unpublish.before' => '内容取消发布前',
            'content.unpublish.after' => '内容取消发布后',
            'content.view' => '内容查看时',
            'content.search' => '内容搜索时',
            'content.comment.add' => '添加评论',
            'content.comment.approve' => '评论审核通过',
            'content.comment.reject' => '评论审核拒绝',
        ],

        // 主题系统钩子
        'theme' => [
            'theme.install.before' => '主题安装前',
            'theme.install.after' => '主题安装后',
            'theme.activate.before' => '主题激活前',
            'theme.activate.after' => '主题激活后',
            'theme.deactivate.before' => '主题停用前',
            'theme.deactivate.after' => '主题停用后',
            'theme.uninstall.before' => '主题卸载前',
            'theme.uninstall.after' => '主题卸载后',
            'theme.update.before' => '主题更新前',
            'theme.update.after' => '主题更新后',
            'theme.customizer.save' => '主题定制保存',
            'theme.tables.create' => '主题表创建',
            'theme.tables.drop' => '主题表删除',
        ],

        // 插件系统钩子
        'plugin' => [
            'plugin.install.before' => '插件安装前',
            'plugin.install.after' => '插件安装后',
            'plugin.activate.before' => '插件激活前',
            'plugin.activate.after' => '插件激活后',
            'plugin.deactivate.before' => '插件停用前',
            'plugin.deactivate.after' => '插件停用后',
            'plugin.uninstall.before' => '插件卸载前',
            'plugin.uninstall.after' => '插件卸载后',
            'plugin.update.before' => '插件更新前',
            'plugin.update.after' => '插件更新后',
            'plugin.config.save' => '插件配置保存',
            'plugin.dependency.check' => '插件依赖检查',
        ],

        // HTTP请求钩子
        'request' => [
            'request.before' => 'HTTP请求开始前',
            'request.after' => 'HTTP请求结束后',
            'request.route.matched' => '路由匹配后',
            'request.middleware.before' => '中间件执行前',
            'request.middleware.after' => '中间件执行后',
            'request.validation.before' => '请求验证前',
            'request.validation.after' => '请求验证后',
            'request.rate_limit.exceeded' => '请求频率限制触发',
            'request.404' => '404错误',
            'request.500' => '500错误',
        ],

        // 响应钩子
        'response' => [
            'response.before' => '响应发送前',
            'response.after' => '响应发送后',
            'response.json.before' => 'JSON响应前',
            'response.json.after' => 'JSON响应后',
            'response.view.before' => '视图渲染前',
            'response.view.after' => '视图渲染后',
            'response.redirect.before' => '重定向前',
            'response.error' => '响应错误',
        ],

        // 数据库钩子
        'database' => [
            'database.query.before' => '数据库查询前',
            'database.query.after' => '数据库查询后',
            'database.transaction.start' => '事务开始',
            'database.transaction.commit' => '事务提交',
            'database.transaction.rollback' => '事务回滚',
            'database.migration.before' => '数据迁移前',
            'database.migration.after' => '数据迁移后',
            'database.seed.before' => '数据填充前',
            'database.seed.after' => '数据填充后',
        ],

        // 缓存钩子
        'cache' => [
            'cache.hit' => '缓存命中',
            'cache.miss' => '缓存未命中',
            'cache.write' => '写入缓存',
            'cache.delete' => '删除缓存',
            'cache.clear' => '清理缓存',
            'cache.flush' => '刷新缓存',
        ],

        // 队列钩子
        'queue' => [
            'queue.job.process' => '队列任务处理',
            'queue.job.processed' => '队列任务处理完成',
            'queue.job.failed' => '队列任务失败',
            'queue.job.retry' => '队列任务重试',
            'queue.worker.start' => '队列工作者启动',
            'queue.worker.stop' => '队列工作者停止',
        ],

        // 文件系统钩子
        'file' => [
            'file.upload.before' => '文件上传前',
            'file.upload.after' => '文件上传后',
            'file.delete.before' => '文件删除前',
            'file.delete.after' => '文件删除后',
            'file.move.before' => '文件移动前',
            'file.move.after' => '文件移动后',
            'file.copy.before' => '文件复制前',
            'file.copy.after' => '文件复制后',
        ],

        // 邮件钩子
        'mail' => [
            'mail.send.before' => '邮件发送前',
            'mail.send.after' => '邮件发送后',
            'mail.send.failed' => '邮件发送失败',
            'mail.template.render' => '邮件模板渲染',
            'mail.queue.add' => '邮件加入队列',
        ],

        // API钩子
        'api' => [
            'api.request.before' => 'API请求前',
            'api.request.after' => 'API请求后',
            'api.response.before' => 'API响应前',
            'api.response.after' => 'API响应后',
            'api.auth.check' => 'API认证检查',
            'api.rate_limit.check' => 'API频率限制检查',
            'api.error' => 'API错误',
        ],

        // SEO钩子
        'seo' => [
            'seo.meta.generate' => 'SEO元标签生成',
            'seo.sitemap.generate' => '站点地图生成',
            'seo.robots.generate' => 'robots.txt生成',
            'seo.url.rewrite' => 'URL重写',
            'seo.breadcrumb.generate' => '面包屑导航生成',
        ],

        // 搜索钩子
        'search' => [
            'search.query.before' => '搜索查询前',
            'search.query.after' => '搜索查询后',
            'search.index.update' => '搜索索引更新',
            'search.index.delete' => '搜索索引删除',
            'search.result.filter' => '搜索结果过滤',
        ],

        // 媒体钩子
        'media' => [
            'media.upload.before' => '媒体上传前',
            'media.upload.after' => '媒体上传后',
            'media.resize.before' => '媒体尺寸调整前',
            'media.resize.after' => '媒体尺寸调整后',
            'media.compress.before' => '媒体压缩前',
            'media.compress.after' => '媒体压缩后',
            'media.watermark.add' => '添加水印',
        ],

        // 安全钩子
        'security' => [
            'security.login.attempt' => '登录尝试',
            'security.login.failed' => '登录失败',
            'security.password.weak' => '弱密码检测',
            'security.brute_force.detected' => '暴力破解检测',
            'security.malware.detected' => '恶意软件检测',
            'security.firewall.block' => '防火墙阻止',
        ],

        // 支付钩子
        'payment' => [
            'payment.process.before' => '支付处理前',
            'payment.process.after' => '支付处理后',
            'payment.success' => '支付成功',
            'payment.failed' => '支付失败',
            'payment.refund.before' => '退款前',
            'payment.refund.after' => '退款后',
            'payment.webhook.receive' => '支付回调接收',
        ],

        // 订单钩子（电商主题）
        'order' => [
            'order.create.before' => '订单创建前',
            'order.create.after' => '订单创建后',
            'order.update.before' => '订单更新前',
            'order.update.after' => '订单更新后',
            'order.cancel.before' => '订单取消前',
            'order.cancel.after' => '订单取消后',
            'order.complete' => '订单完成',
            'order.ship' => '订单发货',
        ],

        // 产品钩子（电商主题）
        'product' => [
            'product.create.before' => '产品创建前',
            'product.create.after' => '产品创建后',
            'product.update.before' => '产品更新前',
            'product.update.after' => '产品更新后',
            'product.delete.before' => '产品删除前',
            'product.delete.after' => '产品删除后',
            'product.stock.update' => '产品库存更新',
            'product.price.change' => '产品价格变更',
        ],

        // 论坛钩子（论坛主题）
        'forum' => [
            'forum.topic.create' => '论坛话题创建',
            'forum.topic.reply' => '论坛话题回复',
            'forum.post.like' => '帖子点赞',
            'forum.post.report' => '帖子举报',
            'forum.user.ban' => '用户禁言',
            'forum.user.unban' => '用户解禁',
        ],

        // 社交钩子
        'social' => [
            'social.share.before' => '社交分享前',
            'social.share.after' => '社交分享后',
            'social.login.before' => '社交登录前',
            'social.login.after' => '社交登录后',
            'social.follow' => '关注用户',
            'social.unfollow' => '取消关注',
        ],

        // 通知钩子
        'notification' => [
            'notification.send.before' => '通知发送前',
            'notification.send.after' => '通知发送后',
            'notification.read' => '通知已读',
            'notification.delete' => '通知删除',
            'notification.email.send' => '邮件通知发送',
            'notification.sms.send' => 'SMS通知发送',
        ],

        // 统计钩子
        'analytics' => [
            'analytics.page.view' => '页面浏览',
            'analytics.user.visit' => '用户访问',
            'analytics.event.track' => '事件追踪',
            'analytics.conversion.track' => '转化追踪',
            'analytics.report.generate' => '报告生成',
        ],

        // 多语言钩子
        'localization' => [
            'lang.change' => '语言切换',
            'lang.load.before' => '语言包加载前',
            'lang.load.after' => '语言包加载后',
            'lang.translate' => '翻译处理',
            'lang.fallback' => '语言回退',
        ],

        // 定时任务钩子
        'schedule' => [
            'schedule.run.before' => '定时任务运行前',
            'schedule.run.after' => '定时任务运行后',
            'schedule.task.start' => '定时任务开始',
            'schedule.task.complete' => '定时任务完成',
            'schedule.task.failed' => '定时任务失败',
        ],

        // 配置钩子
        'config' => [
            'config.load' => '配置加载',
            'config.save' => '配置保存',
            'config.cache.clear' => '配置缓存清理',
            'config.validate' => '配置验证',
            'config.default.load' => '默认配置加载',
        ],

        // 日志钩子
        'log' => [
            'log.write' => '日志写入',
            'log.error' => '错误日志',
            'log.warning' => '警告日志',
            'log.info' => '信息日志',
            'log.debug' => '调试日志',
            'log.rotate' => '日志轮转',
        ],
    ];

    public static function getAllHooks(): array
    {
        return self::$predefinedHooks;
    }

    public static function getHooksByCategory(string $category): array
    {
        return self::$predefinedHooks[$category] ?? [];
    }

    public static function getHookDescription(string $tag): ?string
    {
        foreach (self::$predefinedHooks as $category => $hooks) {
            if (isset($hooks[$tag])) {
                return $hooks[$tag];
            }
        }
        return null;
    }

    public static function isValidHook(string $tag): bool
    {
        foreach (self::$predefinedHooks as $hooks) {
            if (array_key_exists($tag, $hooks)) {
                return true;
            }
        }
        return false;
    }

    public static function searchHooks(string $keyword): array
    {
        $results = [];
        $keyword = strtolower($keyword);

        foreach (self::$predefinedHooks as $category => $hooks) {
            foreach ($hooks as $tag => $description) {
                if (strpos(strtolower($tag), $keyword) !== false || 
                    strpos(strtolower($description), $keyword) !== false) {
                    $results[$tag] = [
                        'category' => $category,
                        'description' => $description,
                    ];
                }
            }
        }

        return $results;
    }

    public static function getHookCount(): int
    {
        $count = 0;
        foreach (self::$predefinedHooks as $hooks) {
            $count += count($hooks);
        }
        return $count;
    }

    public static function getCategoriesCount(): array
    {
        $counts = [];
        foreach (self::$predefinedHooks as $category => $hooks) {
            $counts[$category] = count($hooks);
        }
        return $counts;
    }

    public static function registerCustomHook(string $category, string $tag, string $description): void
    {
        if (!isset(self::$predefinedHooks[$category])) {
            self::$predefinedHooks[$category] = [];
        }
        
        self::$predefinedHooks[$category][$tag] = $description;
    }

    public static function getHookDocumentation(): array
    {
        $docs = [];
        
        foreach (self::$predefinedHooks as $category => $hooks) {
            $docs[$category] = [
                'name' => ucfirst(str_replace('_', ' ', $category)),
                'hooks' => [],
            ];
            
            foreach ($hooks as $tag => $description) {
                $docs[$category]['hooks'][] = [
                    'tag' => $tag,
                    'description' => $description,
                    'parameters' => self::getHookParameters($tag),
                    'return_type' => self::getHookReturnType($tag),
                    'example' => self::getHookExample($tag),
                ];
            }
        }
        
        return $docs;
    }

    protected static function getHookParameters(string $tag): array
    {
        // 这里可以根据钩子标签返回参数定义
        $commonParams = [
            'user.login.after' => ['$user', '$request'],
            'content.create.after' => ['$content', '$request'],
            'payment.success' => ['$payment', '$order'],
            // ... 更多参数定义
        ];

        return $commonParams[$tag] ?? [];
    }

    protected static function getHookReturnType(string $tag): string
    {
        // 根据钩子类型返回期望的返回类型
        if (strpos($tag, '.filter') !== false || strpos($tag, '.modify') !== false) {
            return 'mixed';
        }
        
        return 'void';
    }

    protected static function getHookExample(string $tag): string
    {
        $examples = [
            'user.login.after' => "add_action('user.login.after', function(\$user, \$request) {\n    Log::info('User logged in: ' . \$user->email);\n});",
            'content.create.after' => "add_action('content.create.after', function(\$content) {\n    // 发送通知\n    Notification::send(\$content->author, new ContentPublished(\$content));\n});",
        ];

        return $examples[$tag] ?? "add_action('{$tag}', function() {\n    // 你的代码\n});";
    }
}