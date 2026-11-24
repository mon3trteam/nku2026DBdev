# 数聚 TeamHub 首页高保真附件

本目录是个人作业 2 的设计附件，页面对象为 M3 团队项目“数聚 TeamHub”的公共首页。

## 设计方式

页面原型使用 Axure RP 11 完成，源文件位于 `axure/作业2原型.rp`，包含首页、成员页和项目页三页。Axure 原型用于呈现页面结构、视觉规范和主要页面跳转；首页包含导航“成员”、导航“项目”和“查看成员”三个 Hot Spot；`index.html` 是与原型视觉一致的可运行高保真实现，用于在浏览器中检查真实布局和响应式效果。

## 文件

- `axure/作业2原型.rp`：Axure RP 11 三页原型源文件
- `index.html`：可交互高保真页面，双击即可在浏览器打开
- `../assets/home-wireframe.png`：首页结构稿（含 01-06 分区标注）
- `../assets/home-frontend.png`：桌面端高保真页面
- `../assets/home-mobile.png`：移动端适配效果
- `../assets/admin-backend.png`：后台数据看板模板适配效果
- `design-tokens.css`：项目实际使用的颜色、字体、间距与圆角变量
- `logo.svg`：团队项目现有标识资源

## 页面结构

1. 品牌导航
2. 首屏主信息与主行动
3. 团队数据条
4. 精选项目与最新动态
5. 成员速览
6. 留言转化区

## 模板基础

- 前台：Bootstrap 5.3 Album Example，MIT License
- 后台：Bootstrap 5.3 Dashboard Example，MIT License
- 团队项目实际使用本地 Bootstrap 5.3.8，不依赖外部 CDN，适合校园网络与离线演示。