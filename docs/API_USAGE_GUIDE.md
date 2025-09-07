<!-- Swagger UI 中文使用说明 -->

# Gei5CMS API 接口文档使用指南

访问地址：http://127.0.0.1:8000/api/documentation

## 主要功能

### 1. 查看API接口
- **身份认证接口**：用户登录、登出、获取用户信息
- **插件管理接口**：插件的增删改查、激活停用
- **主题管理接口**：主题的增删改查、激活停用  
- **用户管理接口**：用户管理、权限设置
- **系统设置管理接口**：系统配置管理
- **系统信息和工具**：健康检查、系统信息

### 2. 如何测试API

#### 步骤一：获取访问令牌
1. 点击 **"Authentication"** 分类
2. 展开 **"POST /api/v1/auth/login 用户登录"**
3. 点击 **"Try it out"** 按钮
4. 填入登录信息：
   ```json
   {
     "email": "admin@gei5cms.local",
     "password": "password123"
   }
   ```
5. 点击 **"Execute"** 执行
6. 复制返回结果中的 `token` 值

#### 步骤二：设置认证令牌
1. 点击页面右上角的 **"Authorize"** 按钮
2. 在弹窗中输入：`Bearer 你的token值`（注意Bearer后面有空格）
3. 点击 **"Authorize"** 确认

#### 步骤三：测试其他接口
现在可以测试需要认证的接口了，比如：
- 获取插件列表：`GET /api/v1/plugins`
- 获取用户列表：`GET /api/v1/users`
- 获取系统设置：`GET /api/v1/settings`

### 3. 页面元素说明
- **绿色 GET**：查询数据
- **蓝色 POST**：创建新数据  
- **橙色 PUT/PATCH**：更新数据
- **红色 DELETE**：删除数据
- **Try it out**：测试按钮
- **Execute**：执行按钮
- **Response**：返回结果

### 4. 注意事项
- 所有需要认证的接口都需要先登录获取token
- token格式：`Bearer 你的token值`
- 请求失败时检查参数格式和权限设置