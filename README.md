## hyperf-admin-amazon

#### 背景
> 此项目仅作为本人在公司项目对接SP-API开发过程中的经验总结归纳，本人不保证此项目代码的正确性，请注意甄别。此项目只是完成了整体架构搭建，以及对接了部分API，**其中有些只是对接了但数据并未完成入库**

#### 介绍
> 此项目基于PHP的Hyperf框架开发，需要你对Hyperf框架有一定的了解。

此项目主要代码在`app/Command` 下，
- Amazon  **文件夹存放对接SP-API 代码**
- Auto    **暂定存放创建Controller,Model,Service类文件的自定义命令**
- Crontab **文件夹存放定时任务命令**
- Fake    **文件夹存放手动触发某些动作去创建或构造某些结构，例如构造指定队列数据**
- Monitor **文件夹存放监控系统指定数据，例如监控队列长度等(考虑移动到Crontab文件夹内)**
- Schedule **文件夹存放周期任务(考虑移动到Crontab文件夹内)**

- ``app/Queue`` **存放队列类与队列Data类**
- ``app/Util``  **存放一些工具类与逻辑处理类**

本项目设计时考虑了多商户多店铺的情况，所以大部分表都需要有`merchant_id`与`merchant_store_id`字段。项目初始化时请把相应配置填入`amazon_app`表和`amazon_app_region`表中。


### 常用命令
```
# 刷新Token缓存

crontab:amazon:refresh-app-token

# 创建报告
### 强制创建销售与流量报告， 时间范围为2023-12-01到2023-12-20，循环创建每一天的报告
> php bin/hyperf.php amazon:report:create 1 1 GET_SALES_AND_TRAFFIC_REPORT --report_start_date=2023-12-01 --report_end_date=2023-12-20 --is_range_date=1 --is_force_create=1
# 拉取报告
> php bin/hyperf.php amazon:report:get
# 处理报告
> php bin/hyperf.php amazon:report:action

# 获取周期报告
> php bin/hyperf.php amazon:report:gets 1 1 us-east-1 GET_DATE_RANGE_FINANCIAL_TRANSACTION_DATA
# 拉取周期报告
> php bin/hyperf.php amazon:report:gets-document
# 处理周期报告
> php bin/hyperf.php amazon:report:action-document

```
[即时报告类型报告和周期报告定义](./config/autoload/amazon_reports.php)

##### 项目亮点

1. 性能对比

Hyperf框架与ThinkPHP5相同逻辑处理同一个报告，差距也太大了。Swoole真的强

但目前我没有实现常驻进程的平滑退出，这是我在这项目中困扰的点

![Hyperf](assets/markdown-img-paste-20240207022923459.png)

![ThinkPHP6](assets/markdown-img-paste-20240207023112869.png)


2. 基于Redis List队列封装

3. 基于Redis Hash的封装

