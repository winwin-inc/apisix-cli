# APISIX 命令行工具

## 安装
```bash
composer global require winwin/apisix-cli
```
## 通用参数

- --debug 使用此参数可以打印 apisix 接口调用日志
- --config 指定配置文件路径
- --format 指定输出格式，支持 json 和 ascii (一般使用表格输出)。 默认值 ascii
## configure
configure 命令用于配置ADMIN API接口访问信息，需要提供ADMIN API 地址和 token 。
配置文件保存在 `~/.config/apisix/config.json`

## apply
安装架构即代码的原则，apisix的配置推荐使用配置文件进行管理。
根据文件创建或更新对象。示例：
```json
{
    "consumers": {
        "jsonrpc": {
            "username": "jsonrpc",
            "plugins": {
                "key-auth": {
                    "key": "3dcae3e8fd605065acda2f288272a7a62517c60b"
                }
            }
        }
    },
    "routes": {
        "WinwinWeb_JsonRpcGatewayServer":{
            "uri": "/",
            "host": "jsonrpc.cuntutu.com",
            "plugins": {
                "key-auth": {}
            },
            "upstream_id": "WinwinWeb_JsonRpcGatewayServer"
        }
    }
}
```
配置文件规则为：
```json
{
  "{object_type}": {
    "{object_id}": config
  }
}
```
object_type 目前支持：routes, upstreams, consumers
配置参数参考 [APISIX ADMIN API 文档](https://github.com/apache/apisix/blob/master/docs/zh/latest/admin-api.md)。
## routes
```bash
apisix-cli routes                 # 列出所有配置的 route
apisix-cli routes route1          # 显示 id 为 route1 的路由详情
apisix-cli routes --delete route1 # 删除 id 为 route1 的路由
```
## upstreams
```bash
apisix-cli upstreams                     # 列出所有配置的 upstreams
apisix-cli upstreams upstream1           # 显示 id 为 upstream1 的upstream详情
apisix-cli upstreams --delete upstream1  # 删除 id 为 upstream1 的upstream
apisix-cli upstreams upstream1 --remove-node 10.1.1.204:8000 # 删除 upstream1 的节点
```
## consumers


```bash
apisix-cli consumers                    # 列出所有 consumer
apisix-cli consumers consumer1          # 显示 id 为 consumer1 的详情
apisix-cli consumers --delete consumer1 # 删除 id 为 consumer1 的 consumer
```
