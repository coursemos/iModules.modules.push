<?php
/**
 * 이 파일은 아이모듈 알림모듈의 일부입니다. (https://www.imodules.io)
 *
 * 알림모듈 클래스 정의한다.
 *
 * @file /modules/push/Push.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 11. 6.
 */
namespace modules\push;
class Push extends \Module
{
    /**
     * @var \modules\push\Protocol $_protocol 기본 규약 클래스
     */
    private static \modules\push\Protocol $_protocol;

    /**
     * @var \modules\push\dtos\Code[] $_codes 알림종류
     */
    private static array $_codes;

    /**
     * 모듈을 설정을 초기화한다.
     */
    public function init(): void
    {
    }

    /**
     * 모듈의 컨텍스트 목록을 가져온다.
     *
     * @return array $contexts 컨텍스트목록
     */
    public function getContexts(): array
    {
        $contexts = [];
        foreach ($this->getText('contexts') as $name => $title) {
            $contexts[] = ['name' => $name, 'title' => $title];
        }
        return $contexts;
    }

    /**
     * 컨텍스트를 가져온다.
     *
     * @param string $context 컨텍스트명
     * @param ?object $configs 컨텍스트 설정
     * @return string $html
     */
    public function getContext(string $context, ?object $configs = null): string
    {
        /**
         * 컨텍스트 템플릿을 설정한다.
         */
        if (isset($configs?->template) == true && $configs->template->name !== '#') {
            $this->setTemplate($configs->template);
        } else {
            $this->setTemplate($this->getConfigs('template'));
        }

        $content = '';
        switch ($context) {
            case 'settings':
                $content = $this->getSettingsContext($configs);
                break;

            default:
                $content = \ErrorHandler::get(\ErrorHandler::error('NOT_FOUND_URL'));
        }

        return $this->getTemplate()->getLayout($content);
    }

    /**
     * 알림설정 컨텍스트를 가져온다.
     *
     * @param object $configs
     * @return string $html
     */
    public function getSettingsContext($configs = null): string
    {
        /**
         * 컨텍스트 템플릿을 설정한다.
         */
        if (isset($configs?->template) == true && $configs->template->name !== '#') {
            $this->setTemplate($configs->template);
        } else {
            $this->setTemplate($this->getConfigs('template'));
        }

        $codes = $this->getCodes();
        $template = $this->getTemplate();
        $template->assign('codes', $codes);

        $header = $footer = '';

        return $template->getContext('settings', $header, $footer);
    }

    /**
     * 알림을 전송하기 위한 전송자 클래스를 가져온다.
     *
     * @param \Component $component 알림을 전송하는 컴포넌트 객체
     * @param int $sended_by 알림을 전송하는 회원고유값 (0 인 경우 시스템발송으로 처리)
     * @return \modules\push\Sender $sender
     */
    public function getSender(\Component $component, int $sended_by = 0): \modules\push\Sender
    {
        return new \modules\push\Sender($component, $sended_by);
    }

    /**
     * 특정모듈의 알림 규약 클래스를 가져온다.
     * 해당 클래스가 존재하지 않을 경우 알림모듈의 기본 규약 클래스를 가져온다.
     *
     * @param \Component $target 규약 클래스를 가져올 컴포넌트 객체
     * @return \modules\push\Protocol $protocol
     */
    public function getProtocol(\Component $target): \modules\push\Protocol
    {
        $protocol = parent::getProtocol($target);

        if ($protocol === null) {
            if (isset(self::$_protocol) == false) {
                self::$_protocol = new \modules\push\Protocol($this, $this);
            }

            return self::$_protocol;
        } else {
            return $protocol;
        }
    }

    /**
     * 알림종류 객체를 생성한다.
     *
     * @param \Component $component 알림을 전송하는 컴포넌트 객체
     * @param string $code 알림코드
     * @return \modules\push\dtos\Code $code
     */
    public function setCode(\Component $component, string $code): \modules\push\dtos\Code
    {
        return new \modules\push\dtos\Code($component, $code);
    }

    /**
     * 전체알림코드를 가져온다.
     *
     * @return \modules\push\dtos\Code[] $codes
     */
    public function getCodes(): array
    {
        if (isset(self::$_codes) == false) {
            self::$_codes = [];

            foreach (\Modules::all() as $module) {
                /**
                 * @var \modules\push\Protocol $protocol
                 */
                $protocol = parent::getProtocol($module);
                foreach ($protocol?->getCodes() ?? [] as $code) {
                    self::$_codes[$code->getCode(true)] = $code;
                }
            }

            foreach (\Plugins::all() as $plugin) {
                /**
                 * @var \modules\push\Protocol $protocol
                 */
                $protocol = parent::getProtocol($plugin);
                foreach ($protocol?->getCodes() ?? [] as $code) {
                    self::$_codes[$code->getCode(true)] = $code;
                }
            }
        }

        return array_values(self::$_codes);
    }

    /**
     * 회원설정에 따른 알림수신채널을 가져온다.
     *
     * @param int $member_id 설정을 가져올 회원고유값 (0 인 경우 비회원으로 기본 설정 사용)
     * @param \Component $component 알림을 보내는 컴포넌트 객체
     * @param string $code 알림종류
     * @return string[] $channels 수신채널 (WEB, SMS, EMAIL)
     */
    public function getChannels(int $member_id, \Component $component, string $code): array
    {
        if ($member_id == 0) {
            $setting = null;
        } else {
            $setting = $this->db()
                ->select()
                ->from($this->table('settings'))
                ->where('member_id', $member_id)
                ->where('component_type', $component->getType())
                ->where('component_name', $component->getName())
                ->where('code', $code)
                ->getOne();
        }

        if ($setting === null) {
            return $this->getProtocol($component)->getChannels($member_id, $code);
        } else {
            $channels = [];
            if ($setting->web == 'TRUE') {
                $channels[] = 'WEB';
            }
            if ($setting->sms == 'TRUE') {
                $channels[] = 'SMS';
            }
            if ($setting->email == 'TRUE') {
                $channels[] = 'EMAIL';
            }

            return $channels;
        }
    }
}
