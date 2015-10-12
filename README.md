本插件为免费开源插件，为防止倒卖特放到Github托管.

对此插件的相关问题，可提交Issues，会对相关的问题进行解答.

如果不想提交Issus，想直接一条龙安装架设服务请联系QQ28707775，可提供有偿服务

 WHMCSshadowsocks插件
本插件需要WHMCS 和 Shadowsocks-manyuser


插件放到
Modules/servers/shadowsocks下

服务器配置
 

 

此处填写服务器的IP地址
 
这里填写服务器的MySQL用户密码（要求有远程连接权限，并且至少拥有Shadowsocks表认证表的增删该查权限）

 
配置产品

 
 
 

这是是前台输入密码的地方。第一个框框里面的

一定要一样
一定要一样
一定要一样

重要的事情说三遍。
 

注意：

可配置选项：traffic|流量

老用户安装后执行下这个SQL.txt -> 这个里面的SQL 仅限于之前用过这个这个插件的用户执行。

请老用户导入这个SQL.sql –> 这个仅限于之前用过这个的插件的用户导入。

shadowsocks.sql –> 请所有新用户不要导入原来Shadowsocks里面的shadowsocks.sql 请导入这个。
