# tp5tools




# tp5model 使用方法

首先使用composer 安装
```
composer require  zqtop999/tp5model
``` 
```

然后在command.php文件增加配置

```
'tp5model' => \zqtop999\think\tp5tools\tp5model::class,
```

完成以后

```
php think tp5model index/model #为index模块下model目录的所有的模型文件生成注释
```
也可以
```
php think tp5model index/model/PeopleModel.php #为index模块下model目录的PeopleModel生成注释
```

# 实验案例如下

原来的模型文件内容

```
class PeopleModel extends Model
{
    protected $table = 'new_people';

    /**
     * 人物的工作经历数据
     * @return \think\model\relation\HasMany
     */
    public function careerData()
    {
        return $this->hasMany(CareerModel::class,'people_guid','guid');
    }

    /**
     * 个人的详细信息
     * @return \think\model\relation\HasOne
     */
    public function profileData()
    {
        return $this->hasOne(ProfileModel::class,'people_guid','guid');
    }

    /**
     * 头像的完整路径
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getLogoFullPathAttr($value,$data)
    {
        return resource_url($data['urk']);
    }

}
```
效果如下

```
use think\Model;

/**
 * 人物表（投资者/创业者）
 * @property $guid   人物的guid
 * @property $publish_status   发布状态
 * @property $full_name   用户名称
 * @property $english_name   中文名称
 * @property $gender   性别
 * @property $byline   个性签名
 * @property $avatar_image   头像
 * @property $date_of_birth   出生日期
 * @property $contact_wechat   微信
 * @property $is_entrepreneur   是否是创业
 * @property $claimed_by   认领人
 * @property $claimed_at   认领时间
 * @property $career_data   CareerModel[]   人物的工作经历数据
 * @property $profile_data   ProfileModel   个人的详细信息
 * @property $logo_full_path   头像的完整路径
 */

class PeopleModel extends Model
{

```




