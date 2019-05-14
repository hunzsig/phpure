<?php
/**
 * Created by PhpStorm.
 * Date: 2018/07/16
 */

namespace User\Model;

use User\Bean\InfoBean;

use User\Map\IdentityAuthStatus;
use User\Map\Source;
use User\Map\Status;
use User\Map\Sex;

class InfoModel extends AbstractModel
{

    const FieldStrUser = 'uid,status,inviter_uid,mobile,email,login_name,identity_name,identity_card_no,identity_card_expire_date,wx_open_id,latest_login_time,permission,create_time';
    const FieldStrUserSecret = 'platform,login_pwd_level,safe_pwd,safe_pwd_level,register_ip,source';
    const FieldStrUserIdentityAuth = 'identity_auth_status,identity_auth_reject_reason,identity_auth_time';
    const FieldStrUserInfo = 'sex,nickname,avatar,birthday';
    const FieldStrUserInfoThis = '';
    const FieldStrUserFinance = 'status,balance,freeze_balance,balance_lock,freeze_balance_lock,credit,freeze_credit';

    /**
     * @return InfoBean
     */
    protected function getBean()
    {
        return parent::getBean(); // TODO: Change the autogenerated stub
    }

    /**
     * 获取连表视图
     * @return \library\Mysql
     */
    protected function getViewModel()
    {
        $bean = $this->getBean();
        $table = $this->db()->table('user');
        $table->field(self::FieldStrUser, 'user');
        if ($bean->getWithSecret()) {
            $table->field(self::FieldStrUserSecret, 'user');
        }
        if ($bean->getWithIdentityAuth()) {
            $table->field(self::FieldStrUserIdentityAuth, 'user');
        }
        $table->join('user', 'user_info AS info', array('uid' => 'uid'), 'LEFT');
        $table->field(self::FieldStrUserInfo, 'info');
        if ($bean->getWithThis()) {
            $table->field(self::FieldStrUserInfoThis, 'info');
        }
        if ($bean->getWithFinance()) {
            $table->join('user', 'finance_wallet AS wallet', array('uid' => 'uid'), 'LEFT');
            $table->field(self::FieldStrUserFinance, 'wallet');
        }
        return $table;
    }

    /**
     * @param \library\Mysql $model
     * @return \library\Mysql
     */
    protected function bindWhere($model)
    {
        $bean = $this->getBean();
        $model->whereTable('user');
        $bean->getUid() && $model->in('uid', $bean->getUid());
        $bean->getLoginName() && $model->in('login_name', $bean->getLoginName());
        $bean->getNotUid() && $model->notIn('uid', $bean->getNotUid());
        $bean->getInviterUid() && $model->in('inviter_uid', $bean->getInviterUid());
        $bean->getPlatform() && $model->contains('platform', $bean->getPlatform());
        $bean->getStatus() && $model->in('status', $bean->getStatus());
        $bean->getNotStatus() && $model->notIn('status', $bean->getNotStatus());
        $bean->getIdentityAuthStatus() && $model->in('identity_auth_status', $bean->getIdentityAuthStatus());
        $bean->getMobile() && $model->like('mobile', "%" . $bean->getMobile(false) . "%");
        $bean->getEmail() && $model->like('email', "%" . $bean->getEmail(false) . "%");
        $bean->getIdentityName() && $model->like('identity_name', "%" . $bean->getIdentityName() . "%");
        $bean->getIdentityCardNo() && $model->like('identity_card_no', "%" . $bean->getIdentityCardNo() . "%");
        $bean->getLatestLoginTime() && $model->like('latest_login_time', "%" . $bean->getLatestLoginTime() . "%");
        $bean->getRegisterIp() && $model->like('register_ip', "%" . $bean->getRegisterIp() . "%");
        $bean->getWxOpenId() && $model->contains('wx_open_id', $bean->getWxOpenId(false));
        $bean->getSource() && $model->equalTo('source', $bean->getSource());
        $bean->getCreateTime() && $model->between('create_time', $bean->getCreateTime());

        $model->whereTable('info');
        $bean->getSex() && $model->equalTo('sex', $bean->getSex());
        $bean->getNickname() && $model->like('nickname', "%" . $bean->getNickname() . "%");
        $model->whereTable(null);
        return $model;
    }

    /**
     * @param null $data
     * @param string $response
     * @return array
     */
    protected function success($data = null, $response = 'success')
    {
        if (!is_array($data)) {
            return parent::success($data, $response);
        }
        return parent::success($this->factoryData($data, function ($tempData) {
            $bean = $this->getBean();
            $sexMap = (new Sex())->getKV();
            $statusMap = (new Status())->getKV();
            $sourceMap = (new Source())->getKV();
            $identityAuthStatusMap = (new IdentityAuthStatus())->getKV();
            if ($bean->getWithEcard()) {
                $loginNames = array_column($tempData, 'user_login_name');
                $empList = $this->dbSchool()->schemas('dbo')->table('hr_employee')
                    ->field('empno,empname,empsex,deptname,empje01')
                    ->in('empno', $loginNames)->multi();
                $empListKey = array_column($empList, 'hr_employee_empno');
                $empCombine = array_combine($empListKey, $empList);
            }
            foreach ($tempData as $k => $v) {
                ($v['user_status']) && $tempData[$k]['user_status_label'] = $statusMap[$v['user_status']];
                ($v['info_sex']) && $tempData[$k]['info_sex_label'] = $sexMap[$v['info_sex']];
                if ($bean->getWithIdentityAuth()) {
                    ($v['user_identity_auth_status']) && $tempData[$k]['user_identity_auth_status_label'] = $identityAuthStatusMap[$v['user_identity_auth_status']];
                }
                if ($bean->getWithSecret()) {
                    ($v['user_source']) && $tempData[$k]['user_source_label'] = $sourceMap[$v['user_source']];
                    $tempData[$k]['user_is_set_safe_pwd'] = $v['user_safe_pwd'] ? true : false;
                    unset($tempData[$k]['user_safe_pwd']);
                }
                $tempData[$k]['user_check_path'] = ($v['user_uid'] !== 1);
                $tempData[$k]['user_status'] = (string)$v['user_status'];
                $tempData[$k]['info_sex'] = (string)$v['info_sex'];
                if ($bean->getWithWx()) {
                    if ($v['user_wx_open_id']) {
                        $tempData[$k]['user_wx'] = $this->db()->table('external_wx_user_info')->in('open_id', $v['user_wx_open_id'])->multi();
                    } else {
                        $tempData[$k]['user_wx'] = array();
                    }
                }
                unset($tempData[$k]['user_wx_open_id']);
                if ($bean->getWithEcard() && !empty($empCombine[$v['user_login_name']])) {
                    $tempData[$k] = array_merge($tempData[$k], $empCombine[$v['user_login_name']]);
                }
            }
            return $tempData;
        }), $response);
    }

    /**
     * 检测账号信息正确性
     * @param $bean
     * @return bool
     */
    protected function checkAccountValidity(InfoBean $bean)
    {
        $uid = $bean->getUid();
        //todo 检查手机的正确性
        if ($bean->getMobile()) {
            foreach ($bean->getMobile() as $t) {
                if (!isMobile($t)) {
                    return $this->false($t . '不是正确的手机号码');
                    break;
                }
            }
        }
        //todo 检查邮箱的正确性
        if ($bean->getEmail()) {
            foreach ($bean->getEmail() as $t) {
                if (!isEmail($t)) return $this->false($t . '不是正确的邮箱地址');
                break;
            }
        }
        //todo 检查微信OPENID的正确性
        if ($bean->getWxOpenId()) {
            foreach ($bean->getWxOpenId() as $t) {
                if (!isWechatOpenId($t)) {
                    return $this->false($t . '不是正确的微信帐号格式');
                    break;
                }
            }
        }
        //todo 检查身份证号的正确性
        if ($bean->getIdentityCardNo() && !isIdentityCardNo($bean->getIdentityCardNo())) {
            return $this->false('请输入正确身份证号');
        }
        //todo 非查表的检查个性登录名的正确性（假如有的话）
        if ($bean->getLoginName() && !$this->getLoginNameHelper()->checkName($bean->getLoginName())) {
            return $this->false($this->getLoginNameHelper()->getFalseMsg());
        }
        //todo 检查密码的正确性
        if ($bean->getLoginPwd() && !$this->getPasswordHelper()->checkPwd($bean->getLoginPwd())) {
            return $this->false($this->getPasswordHelper()->getFalseMsg());
        }
        if ($bean->getLoginPwdConfirm() && $bean->getLoginPwd() !== $bean->getLoginPwdConfirm()) {
            return $this->false('请确保两次密码一致');
        }
        //todo 检查安全码的正确性
        if ($bean->getSafePwd()) {
            if (!$this->getPasswordHelper()->checkPwd($bean->getSafePwd())) {
                return $this->false($this->getPasswordHelper()->getFalseMsg());
            }
            if (!$bean->getSafePwdConfirm()) {
                return $this->false('请确认安全码');
            }
            if ($bean->getSafePwd() !== $bean->getSafePwdConfirm()) {
                return $this->false('两次安全码不一致');
            }
        }
        //todo 检验用户
        $identity = null;
        if ($bean->getMobile()) {
            foreach ($bean->getMobile() as $t) {
                if ($this->isUsedByIdentity($t, $uid)) {
                    return $this->false($t . '手机号已被他人注册，请输入其他手机');
                    break;
                }
            }
        }
        if ($bean->getEmail()) {
            foreach ($bean->getEmail() as $t) {
                if ($this->isUsedByIdentity($t, $uid)) {
                    return $this->false($t . '邮箱地址已被他人注册，请输入其他手机');
                    break;
                }
            }
        }
        if ($bean->getWxOpenId()) {
            foreach ($bean->getWxOpenId() as $t) {
                if ($this->isUsedByIdentity($t, $uid)) {
                    return $this->false($t . '微信帐号已不被允许进行注册');
                    break;
                }
            }
        }
        if ($bean->getWxUnionid()) {
            foreach ($bean->getWxUnionid() as $t) {
                if ($this->isUsedByIdentity($t, $uid)) {
                    return $this->false($t . '微信Unionid已不被允许进行注册');
                    break;
                }
            }
        }
        if ($bean->getIdentityCardNo() && $this->isUsedByIdentity($bean->getIdentityCardNo(), $uid)) {
            return $this->false('此身份证号已经记录在库不允许再次注册');
        }
        if ($bean->getLoginName() && $this->isUsedByIdentity($bean->getLoginName(), $uid)) {
            return $this->false('此个性登录名已被注册，请使用其他名称');
        }
        return true;
    }

    private function getPermissionValue($data, $prevKey = array(), $result = array())
    {
        foreach ($data as $d) {
            $tempKey = $prevKey;
            $tempKey[] = $d['key'];
            $result[] = implode('-', $tempKey);
            if (!empty($d['children']) && is_array($d['children'])) {
                $result = $this->getPermissionValue($d['children'], $tempKey, $result);
            }
        }
        return $result;
    }

    protected function getDefaultPermission($platform)
    {
        $permission = array();
        $sysPerData = $this->db()->table('system_data')->equalTo('key', 'permission')->one();
        if (!$sysPerData || empty($sysPerData['system_data_data'])) return $permission;
        $sysPerData = $sysPerData['system_data_data'];
        $sysPerDataKeys = array_column($sysPerData, 'key');
        $sysPerData = array_combine($sysPerDataKeys, $sysPerData);
        if (!is_array($platform)) $platform = (array)$platform;
        if (in_array('admin', $platform)) { /* nothing */
        }
        if (in_array('normal', $platform) && !empty($sysPerData['normal'])) {
            $pd = $this->getPermissionValue(array($sysPerData['normal']));
            $permission = array_merge($permission, $pd);
        }
        return $permission;
    }

    //-------------------------------------------------------------

    /**
     * 获取会员列表
     * @return array
     */
    public function getList()
    {
        $bean = $this->getBean();
        $model = $this->getViewModel();;
        $model = $this->bindWhere($model);
        $model->whereTable('user')->notEqualTo('uid', 1)->notEqualTo('status', Status::DELETE);
        if ($bean->getOrderBy()) {
            $model->orderByStr($bean->getOrderBy());
        } else {
            $model->orderBy('uid', 'desc', 'user');
        }
        if ($bean->getPage()) {
            $result = $model->page($bean->getPageCurrent(), $bean->getPagePer());
        } else {
            $result = $model->multi();
        }
        return $this->success($result);
    }

    /**
     * 获取会员帐号信息
     * @return array
     */
    public function getInfo()
    {
        $bean = $this->getBean();
        if (!$bean->getUid()) {
            return $this->error('lose uid');
        }
        $model = $this->getViewModel();
        $model = $this->bindWhere($model);
        $result = $model->one();
        return $this->success($result);
    }

    /**
     * @param InfoBean $bean
     * @param null $isCheckAuthCode
     * @return boolean
     */
    public function add__($bean, $isCheckAuthCode = null)
    {
        if (!$bean->getPlatform()) {
            return $this->false('请选择平台');
        }
        $authUserInfo = null;
        if ($bean->getAuthUid() && in_array($bean->getAuthUid(), $this->getOnlineUid())) {
            $authUserInfo = $this->db()->table('user')->field('uid,platform')->equalTo('uid', $bean->getAuthUid())->one();
        }
        if ($isCheckAuthCode === null) {
            if (!$authUserInfo || !in_array('admin', $authUserInfo['user_platform'])) {
                if (!$bean->getAuthCode()) return $this->false('请输入验证码');
                $isCheckAuthCode = true; // todo 非admin检查
            } else {
                $isCheckAuthCode = false;
            }
        }
        if (!$this->checkAccountValidity($bean)) {
            return $this->false($this->getFalseMsg());
        }
        //todo 如果没有个性登录名，根据UID创建一个默认登录名
        if (!$bean->getLoginName()) {
            $bean->setLoginName($this->getLoginNameHelper()->createDefaultName());
        }
        //todo 检测邀请人
        if (!$bean->getInviterUid() && $bean->getInviterMobile()) {
            $inviterUser = $this->db()->table('user')->field('uid')
                ->equalTo('mobile', $bean->getInviterMobile())
                ->one();
            if (!$inviterUser) {
                return $this->false('无此邀请人');
            }
            $bean->setInviterUid($inviterUser['user_uid']);
        }
        //
        $this->db()->beginTrans();
        try {
            // level 1
            $data = array();
            $data['create_time'] = $this->db()->now();
            $data['status'] = $bean->getStatus() ? $bean->getStatus() : Status::UNVERIFY;
            $data['platform'] = $bean->getPlatform();
            $data['source'] = $this->getSource();
            $data['register_ip'] = $this->getClientIP();
            if ($bean->getPermission()) {
                $data['permission'] = $bean->getPermission();
            } else {
                $data['permission'] = $this->getDefaultPermission($bean->getPlatform());
            }
            ($bean->getMobile()) && $data['mobile'] = $bean->getMobile();
            ($bean->getEmail()) && $data['email'] = $bean->getEmail();
            ($bean->getWxOpenId()) && $data['wx_open_id'] = $bean->getWxOpenId();
            ($bean->getWxUnionid()) && $data['wx_unionid'] = $bean->getWxUnionid();
            ($bean->getIdentityName()) && $data['identity_name'] = $bean->getIdentityName();
            ($bean->getIdentityCardNo()) && $data['identity_card_no'] = $bean->getIdentityCardNo();
            ($bean->getLoginName()) && $data['login_name'] = $bean->getLoginName();
            ($bean->getInviterUid()) && $data['inviter_uid'] = $bean->getInviterUid();
            if ($bean->getLoginPwd()) {
                $data['login_pwd'] = $this->getPasswordHelper()->Password($bean->getLoginPwd());
                $data['login_pwd_level'] = $this->getPasswordHelper()->getPwdLevel($bean->getLoginPwd());
            }
            if ($bean->getSafePwd()) {
                $data['safe_pwd'] = $this->getPasswordHelper()->Password($bean->getSafePwd());
                $data['safe_pwd_level'] = $this->getPasswordHelper()->getPwdLevel($bean->getSafePwd());
            }
            if (!$this->db()->table('user')->insert($data)) {
                throw new \Exception($this->db()->getError());
            }
            $uid = $this->db()->lastInsertId();
            // level 2
            $infoData = array();
            $infoData['uid'] = $uid;
            $infoData['sex'] = (string)($bean->getSex() ? $bean->getSex() : Sex::UN_KNOW);
            $bean->getBirthday() && $infoData['birthday'] = $bean->getBirthday();
            $bean->getNickname() && $infoData['nickname'] = $bean->getNickname();
            $bean->getAvatar() && $infoData['avatar'] = $bean->getAvatar();
            //
            if (!$this->db()->table('user_info')->insert($infoData)) {
                throw new \Exception($this->db()->getError());
            }

            //todo 最后再来执行验证码判断的原因是，减少发送验证码的次数
            if ($isCheckAuthCode == true) {
                $authModel = $this->getSystemAuthModel();
                $result = false;
                if (isMobile($bean->getMobile(false))) {
                    $result = $authModel->authCheckMobile__($bean->getMobile(false), $bean->getAuthCode());
                } elseif (isEmail($bean->getEmail(false))) {
                    $result = $authModel->authCheckEmail__($bean->getEmail(false), $bean->getAuthCode());
                }
                if (!$result) throw new \Exception($authModel->getFalseMsg());
            }
        } catch (\Exception $e) {
            $this->db()->rollBackTrans();
            return $this->false($e->getMessage());
        }
        $this->db()->commitTrans();
        return $uid;
    }

    /**
     * @param InfoBean $bean
     * @return boolean
     */
    public function edit__($bean)
    {
        if (!$bean->getUid()) {
            return $this->false('fail uid');
        }
        if (!$this->isExistByUid($bean->getUid())) {
            return $this->false('用户不存在');
        }
        $loginName = $this->getLoginNameByUid($bean->getUid());
        if ($bean->getLoginName() && $bean->getLoginName() == $loginName) {
            $bean->setLoginName(null);
        }
        if (!$this->checkAccountValidity($bean)) {
            return $this->false($this->getFalseMsg());
        }
        if ($bean->getMobile() || $bean->getEmail() || $bean->getWxOpenId() || $bean->getWxUnionid() || $bean->getLoginPwd() || $bean->getLoginName() || $bean->getSafePwd()) {
            if (!$this->authSafePwd($bean->getUid(), $bean->getCurrentSafePwd())) {
                $bean->setSafePwd(null);
                return $this->false($this->getFalseMsg());
            }
        }
        $this->db()->beginTrans();
        try {
            // level 1
            $data = array();
            $data['update_time'] = $this->db()->now();
            ($bean->getPlatform()) && $data['platform'] = $bean->getPlatform();
            ($bean->getPermission() !== null) && $data['permission'] = $bean->getPermission();
            ($bean->getMobile()) && $data['mobile'] = $bean->getMobile();
            ($bean->getEmail()) && $data['email'] = $bean->getEmail();
            ($bean->getWxOpenId()) && $data['wx_open_id'] = $bean->getWxOpenId();
            ($bean->getWxUnionid()) && $data['wx_unionid'] = $bean->getWxUnionid();
            ($bean->getIdentityName()) && $data['identity_name'] = $bean->getIdentityName();
            ($bean->getIdentityCardNo()) && $data['identity_card_no'] = $bean->getIdentityCardNo();
            ($bean->getLoginName()) && $data['login_name'] = $bean->getLoginName();
            if (($bean->getLoginPwd())) {
                $data['login_pwd'] = $this->getPasswordHelper()->Password($bean->getLoginPwd());
                $data['login_pwd_level'] = $this->getPasswordHelper()->getPwdLevel($bean->getLoginPwd());
            }
            if (($bean->getSafePwd())) {
                $data['safe_pwd'] = $this->getPasswordHelper()->Password($bean->getSafePwd());
                $data['safe_pwd_level'] = $this->getPasswordHelper()->getPwdLevel($bean->getSafePwd());
            }
            $data && $this->db()->table('user')->equalTo('uid', $bean->getUid())->update($data);
            // level 2
            $infoData = array();
            $bean->getSex() && $infoData['sex'] = $bean->getSex();
            $bean->getBirthday() && $infoData['birthday'] = $bean->getBirthday();
            $bean->getNickname() && $infoData['nickname'] = $bean->getNickname();
            $bean->getAvatar() && $infoData['avatar'] = $bean->getAvatar();
            //
            $infoData && $this->db()->table('user_info')->equalTo('uid', $bean->getUid())->update($infoData);
        } catch (\Exception $e) {
            $this->db()->rollBackTrans();
            return $this->false($e->getMessage());
        }
        $this->db()->commitTrans();
        return $bean->getUid();
    }

    /**
     * 添加
     * @return array
     */
    public function add()
    {
        if (!$uid = $this->add__($this->getBean())) {
            return $this->error($this->getFalseMsg());
        }
        return $this->success($uid);
    }

    /**
     * 编辑
     * @return array
     */
    public function edit()
    {
        if (!$uid = $this->edit__($this->getBean())) {
            return $this->error($this->getFalseMsg());
        }
        return $this->success($uid);
    }

    /**
     * 初始化统计db对象
     * @return \library\Mysql
     */
    private function resetStatModel()
    {
        $model = $this->db()->table('user')->join('user', 'user_info AS info', array('uid' => 'uid'), 'INNER');
        $model = $this->bindWhere($model);
        $model->field('uid as whole_qty', 'user', 'COUNT(%0)');
        return $model;
    }

    /**
     * 用户统计
     * @return array
     */
    public function stat()
    {
        $stat = array(
            'all' => array(
                'label' => '全部',
                'total' => 0,
                'is_special_class' => 0,
                'is_retirement' => 0,
                'is_probationary' => 0,
            ),
        );
        $model = $this->resetStatModel();
        $result = $model->multi();
        foreach ($result as $v) {
            $stat['all']['total'] += $v['user_whole_qty'];
        }
        return $this->success($stat);
    }

    //-------------------------------------------------------------

    /**
     * 修改会员状态
     * @param $uid
     * @param $status
     * @return array
     */
    private function changeUserStatus($uid, $status)
    {
        if (!$uid) return $this->error('lose uid');
        try {
            $this->db()->table('user')->in('uid', $uid)->update(array('status' => $status));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 会员 - 正常（通过）
     * @return array
     */
    public function status2normal()
    {
        $uid = $this->getBean()->getUid();
        return $this->changeUserStatus($uid, Status::NORMAL);
    }

    /**
     * 会员 - 注销
     * @return array
     */
    public function status2delete()
    {
        $uid = $this->getBean()->getUid();
        if (!$uid) return $this->error('lose uid');
        try {
            $this->db()->table('user_login_online')->equalTo('uid', $uid)->delete();
        } catch (\Exception $e) {
        }
        $info = $this->db()->table('user')->where(array('uid' => $uid))->one();
        if (!$info) return $this->error('非法用户');
        $this->db()->beginTrans();
        try {
            $this->db()->table('user')->equalTo('uid', $uid)->update(
                array(
                    'login_name' => $this->getLoginNameHelper()->createDeleteName(),
                    'mobile' => '',
                    'email' => '',
                    'wx_open_id' => '',
                    'wx_unionid' => '',
                    'record' => $info,
                    'status' => Status::DELETE,
                    'delete_time' => $this->db()->now(),
                )
            );
        } catch (\Exception $e) {
            $this->db()->rollBackTrans();
            return $this->error($e->getMessage());
        }
        $this->db()->commitTrans();
        return $this->success();
    }

    /**
     * 会员 - 不通过
     * @return array
     */
    public function status2unPass()
    {
        $uid = $this->getBean()->getUid();
        try {
            $this->db()->table('user_login_online')->equalTo('uid', $uid)->delete();
        } catch (\Exception $e) {
        }
        return $this->changeUserStatus($uid, Status::UNPASS);
    }

    /**
     * 会员 - 冻结
     * @return array
     */
    public function status2freeze()
    {
        $uid = $this->getBean()->getUid();
        try {
            $this->db()->table('user_login_online')->equalTo('uid', $uid)->delete();
        } catch (\Exception $e) {
        }
        return $this->changeUserStatus($uid, Status::FREEZE);
    }


    /**
     * 修改登录密码 - 需要手机|邮箱验证
     * @return array
     */
    public function changePassword()
    {
        $bean = $this->getBean();
        $newPassword = $bean->getLoginPwd();
        $confirmPassword = $bean->getLoginPwdConfirm();
        $authName = $bean->getAuthName();
        $authCode = $bean->getAuthCode();
        $uid = $bean->getUid();
        if (!$authName) return $this->error('请输入验证手机或邮箱');
        if (!$authCode) return $this->error('请输入验证码');
        if (!$newPassword) return $this->error('请输入新设定密码');
        if (!$this->getPasswordHelper()->checkPwd($newPassword)) {
            return $this->error($this->getPasswordHelper()->getFalseMsg());
        }
        if ($confirmPassword && $newPassword != $confirmPassword) {
            return $this->error('请确保两次密码一致');
        }

        //修改操作
        $info = $this->getInfoByAccount($authName);
        if (!$info) return $this->error('验证失败');
        $this->db()->beginTrans();
        try {
            //判断验证码
            $model = $this->getSystemAuthModel();
            if (isMobile($authName) && !$model->authCheckMobile__($authName, $authCode, $uid)) {
                throw new \Exception($model->getFalseMsg());
            }
            if (isEmail($authName) && !$model->authCheckEmail__($authName, $authCode, $uid)) {
                throw new \Exception($model->getFalseMsg());
            }
            $this->db()->table('user')->equalTo('uid', $uid)->update(
                array(
                    'login_pwd' => $this->getPasswordHelper()->Password($newPassword),
                    'login_pwd_level' => $this->getPasswordHelper()->getPwdLevel($newPassword),
                )
            );
        } catch (\Exception $e) {
            $this->db()->rollBackTrans();
            return $this->error($e->getMessage());
        }
        $this->db()->commitTrans();
        return $this->success();
    }

    /**
     * 修改邮箱 - 需要邮箱验证
     * @return array
     */
    public function changeEmail()
    {
        $bean = $this->getBean();
        $email = $bean->getEmail();
        $authCode = $bean->getAuthCode();
        $uid = $bean->getUid();
        if (!$uid) return $this->error('账号错误');
        if (!$email) return $this->error('缺少邮箱');
        if (!isEmail($email)) return $this->error('邮箱格式错误');
        if (!$authCode) return $this->error('缺少验证码');
        if ($this->isUsedByIdentity($email, $uid)) {
            return $this->error('邮箱地址已被其他账号绑定');
        }
        //判断验证码
        $model = $this->getSystemAuthModel();
        if (!$model->authCheckEmail__($email, $authCode, $uid)) {
            return $this->error($model->getFalseMsg());
        }
        try {
            $this->db()->table('user')->where(array('uid' => $uid))->update(array('email' => $email));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 修改手机 - 需要手机验证
     * @return array
     */
    public function changeMobile()
    {
        $bean = $this->getBean();
        $mobile = $bean->getMobile();
        $authCode = $bean->getAuthCode();
        $newMobile = $bean->getNewMobile();
        $newAuthCode = $bean->getNewAuthCode();
        $uid = $bean->getUid();
        if (!$uid) return $this->error('账号错误');
        if (!$mobile) return $this->error('缺少原手机');
        if (!isMobile($mobile)) return $this->error('原手机号码格式错误');
        if (!$authCode) return $this->error('请输入原手机验证码');
        if (!$newMobile) return $this->error('缺少新手机');
        if (!isMobile($newMobile)) return $this->error('新手机号码格式错误');
        if (!$newAuthCode) return $this->error('请输入新手机验证码');
        if ($this->isUsedByIdentity($newMobile, $uid)) {
            return $this->error('新手机号已被其他账号绑定，不可使用');
        }
        //判断验证码（原本）
        $model = $this->getSystemAuthModel();
        if (!$model->authCheckMobile__($mobile, $authCode, $uid)) {
            return $this->error($model->getFalseMsg());
        }
        //判断验证码（新的）
        if (!$model->authCheckMobile__($newMobile, $newAuthCode, $uid)) {
            return $this->error($model->getFalseMsg());
        }
        try {
            $this->db()->table('user')->where(array('uid' => $uid))->update(array('mobile' => $newMobile));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 修改个性登录名
     * @return array
     */
    public function changeLoginName()
    {
        $bean = $this->getBean();
        if (!$this->getLoginNameHelper()->checkName($bean->getLoginName())) {
            return $this->error($this->getLoginNameHelper()->getFalseMsg());
        }
        $data = $this->db()->table('user')->where(array('login_name' => $bean->getLoginName()))->one();
        if ($data) {
            if ($data['uid'] == $bean->getUid()) {
                return $this->success();
            } else {
                return $this->error('个性登录名已被他人使用');
            }
        }
        try {
            $this->db()->table('user')
                ->equalTo('uid', $bean->getUid())
                ->update(array('login_name' => $bean->getLoginName()));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 绑定邀请人
     * @return array
     */
    public function bindInviter()
    {
        $bean = $this->getBean();
        if (!$bean->getUid()) return $this->error('参数错误');
        if (!$bean->getInviterUid()) return $this->error('缺少邀请人');

        //检查绑定人是否存在及已经绑定
        $inviter = $this->db()->table('user')->field('uid')->where(array('uid' => $bean->getInviterUid()))->one();
        if (!$inviter) return $this->error('邀请人不存在');

        $one = $this->db()->table('user')->field('uid,inviter_uid')->where(array('uid' => $bean->getUid()))->one();
        if (!$one) return $this->error('用户不存在');
        if ($one['uid'] == $bean->getInviterUid()) return $this->error('你不要邀请自己');
        if ($one['inviter_uid']) return $this->error('你已经被邀请过了');
        try {
            $this->db()->table('user')->update(
                array('inviter_uid' => $bean->getInviterUid()),
                array('uid' => $bean->getUid())
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 获取验证列表
     * @return array
     */
    public function getIdentityAuthList()
    {
        $bean = $this->getBean();
        $model = $this->db()->table('user');
        $model = $this->bindWhere($model);
        $model->orderBy('identity_auth_status', 'desc');
        if ($bean->getPage()) {
            $result = $model->page($bean->getPageCurrent(), $bean->getPagePer());
        } else {
            $result = $model->multi();
        }
        return $this->success($result);
    }

    /**
     * @return array
     */
    public function identityAuthCommit()
    {
        $bean = $this->getBean();
        if (!$bean->getUid()) return $this->error('参数错误');
        $data = array();
        $data['identity_auth_time'] = $this->db()->now();
        $data['identity_auth_status'] = IdentityAuthStatus::CHECKING;
        $bean->getIdentityCardPicFront() && $data['identity_card_pic_front'] = $bean->getIdentityCardPicFront();
        $bean->getIdentityCardPicBack() && $data['identity_card_pic_back'] = $bean->getIdentityCardPicBack();
        $bean->getIdentityCardPicTake() && $data['identity_card_pic_take'] = $bean->getIdentityCardPicTake();
        try {
            $this->db()->table('user')->equalTo('uid', $bean->getUid())->update($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * @return array
     */
    public function identityAuthPass()
    {
        $bean = $this->getBean();
        if (!$bean->getUid()) return $this->error('参数错误');
        if (!$bean->getIdentityName()) return $this->error('请填写身份证姓名');
        if (!$bean->getIdentityCardNo()) return $this->error('请填写身份证号码');
        if (!isIdentityCardNo($bean->getIdentityCardNo())) return $this->error('请输入正确的身份证号码');
        if (!$bean->getIdentityCardExpireDate()) return $this->error('请填写证件过期日期');
        $data = array();
        $data['identity_auth_time'] = $this->db()->now();
        $data['identity_auth_status'] = IdentityAuthStatus::CHECKED;
        $data['identity_name'] = $bean->getIdentityName();
        $data['identity_card_no'] = $bean->getIdentityCardNo();
        $data['identity_card_expire_date'] = $bean->getIdentityCardExpireDate();
        try {
            $this->db()->table('user')->equalTo('uid', $bean->getUid())->update($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * @return array
     */
    public function identityAuthReject()
    {
        $bean = $this->getBean();
        if (!$bean->getUid()) return $this->error('参数错误');
        if (!$bean->getIdentityAuthRejectReason()) return $this->error('请填写拒绝理由');
        $data = array();
        $data['identity_auth_time'] = $this->db()->now();
        $data['identity_auth_status'] = IdentityAuthStatus::UN_PASS;
        $data['identity_auth_reject_reason'] = $bean->getIdentityAuthRejectReason() ?: '';
        try {
            $this->db()->table('user')->equalTo('uid', $bean->getUid())->update($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

}