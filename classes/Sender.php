<?php
/**
 * 이 파일은 아이모듈 알림모듈의 일부입니다. (https://www.imodules.io)
 *
 * 알림 전송자 클래스를 정의한다.
 *
 * @file /modules/push/classes/Sender.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 13.
 */
namespace modules\push;
class Sender
{
    /**
     * @var \Component $_component 알림을 전송하는 컴포넌트 객체
     */
    private \Component $_component;

    /**
     * @var int $_sended_by 알림을 전송하는 회원고유값 (0 인 경우 시스템발송으로 처리)
     */
    private int $_sended_by;

    /**
     * @var int $_member_id 수신자 회원고유값
     */
    private int $_member_id;

    /**
     * @var ?string $_name 수신자명
     */
    private ?string $_name;

    /**
     * @var int $_email 수신자 이메일주소
     */
    private ?string $_email;

    /**
     * @var ?string $_cellphone 수신자 휴대전화번호
     */
    private ?string $_cellphone;

    /**
     * @var string $_target_type 알림대상종류
     */
    private string $_target_type;

    /**
     * @var string|int $_target_id 알림대상고유값
     */
    private string|int $_target_id;

    /**
     * @var string $_mode 알림전송모드 (NEW, MERGE, REPLACE)
     */
    private string $_mode = 'MERGE';

    /**
     * @var string $_code 알림종류
     */
    private string $_code;

    /**
     * @var mixed $_content 본문내용
     */
    private mixed $_content;

    /**
     * @var \modules\push\dtos\Message $_message
     */
    private \modules\push\dtos\Message $_message;

    /**
     * @var string $_type 발송타입
     */
    private string $_type;

    /**
     * 알림 전송자 클래스를 정의한다.
     *
     * @param \Component $component 알림을 전송하는 컴포넌트 객체
     * @param int $sended_by 알림을 전송하는 회원고유값 (0 인 경우 시스템발송으로 처리)
     */
    public function __construct(\Component $component, int $sended_by = 0)
    {
        $this->_component = $component;
        $this->_sended_by = $sended_by;
    }

    /**
     * 수신자를 설정한다.
     *
     * @param int $member_id 수신자 회원고유값 (0 인 경우 비회원으로 이메일 또는 전화번호를 추가로 설정해주어야 한다.)
     * @param ?string $name 수신자명
     * @param ?string $email 이메일주소
     * @param ?string $cellphone 휴대전화번호
     * @return \modules\push\Sender $this
     */
    public function setTo(
        int $member_id,
        ?string $name = null,
        ?string $email = null,
        ?string $cellphone = null
    ): \modules\push\Sender {
        $this->_member_id = $member_id;
        $this->_name = $name;
        $this->_email = $email;
        $this->_cellphone = $cellphone;

        return $this;
    }

    /**
     * 알림대상을 설정한다.
     *
     * @param string $target_type 알림대상종류
     * @param string|int $target_id 알림대상고유값
     * @return \modules\push\Sender $this
     */
    public function setTarget(string $target_type, string|int $target_id): \modules\push\Sender
    {
        $this->_target_type = $target_type;
        $this->_target_id = $target_id;
        return $this;
    }

    /**
     * 알림전송모드를 변경한다.
     *
     * @param string $mode (NEW : 신규알림, MERGE : 읽지 않은 기존알림이 존재할 경우 병합, REPLACE : 기존알림대체)
     * @return \modules\push\Sender $this
     */
    public function setMode(string $mode): \modules\push\Sender
    {
        if (in_array($mode, ['NEW', 'MERGE', 'REPLACE']) == true) {
            $this->_mode = $mode;
        }
        return $this;
    }

    /**
     * 본문내용을 설정한다.
     *
     * @param string $code 알림종류
     * @param mixed $content 본문
     * @return \modules\push\Sender $this
     */
    public function setContent(string $code, mixed $content): \modules\push\Sender
    {
        $this->_code = $code;
        $this->_content = $content;
        return $this;
    }

    /**
     * 발송타입을 가져온다.
     *
     * @param string $type 발송타입
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * 발송타입을 설정한다.
     *
     * @param string $type 발송타입
     */
    public function setType(string $type): void
    {
        $this->_type = $type;
    }

    /**
     * 알림메시지 객체를 생성한다.
     *
     * @return \modules\push\dtos\Message $message
     */
    public function getMessage(): \modules\push\dtos\Message
    {
        if (isset($this->_message) == false) {
            $message = new \stdClass();

            $message->message_id = null;
            $message->member_id = $this->_member_id;
            $message->component_type = $this->_component->getType();
            $message->component_name = $this->_component->getName();
            $message->target_type = $this->_target_type;
            $message->target_id = $this->_target_id;
            $message->code = $this->_code;
            $message->contents = json_encode([$this->_content]);
            $message->sended_by = $this->_sended_by;
            $message->sended_at = time();
            $message->is_checked = 'FALSE';
            $message->is_readed = 'FALSE';

            $this->_message = new \modules\push\dtos\Message($message);
        }

        return $this->_message;
    }

    /**
     * 알림을 전송한다.
     *
     * @param ?int $sended_at - 전송시각(NULL 인 경우 현재시각)
     * @return bool $success 성공여부
     */
    public function send(?int $sended_at = null): bool
    {
        if (
            isset($this->_member_id) == false ||
            isset($this->_component) == false ||
            isset($this->_target_type) == false ||
            isset($this->_code) == false
        ) {
            return false;
        }

        if (isset($this->_message) == false) {
            $this->_message = $this->getMessage();
        }

        $sended_at ??= time();

        /**
         * @var \modules\push\Push $mPush
         */
        $mPush = \Modules::get('push');
        $channels = $mPush->getChannels($this->_member_id, $this->_component, $this->_code);
        foreach ($channels as $channel) {
            if ($this->_member_id > 0 && $channel == 'WEB') {
                /**
                 * 전송모드가 병합 또는 대치일 경우 기존의 알림을 가져온다.
                 */
                if ($this->_mode == 'MERGE' || $this->_mode == 'REPLACE') {
                    $message = $mPush
                        ->db()
                        ->select()
                        ->from($mPush->table('messages'))
                        ->where('member_id', $this->_member_id)
                        ->where('component_type', $this->_component->getType())
                        ->where('component_name', $this->_component->getName())
                        ->where('target_type', $this->_target_type)
                        ->where('target_id', $this->_target_id)
                        ->where('code', $this->_code);
                    if ($this->_mode == 'MERGE') {
                        $message->where('is_checked', 'FALSE');
                    }
                    $message = $message->getOne();
                } else {
                    $message = null;
                }

                if ($this->_mode == 'MERGE') {
                    $contents = array_merge(json_decode($message?->contents ?? '[]'), [$this->_content]);
                } else {
                    $contents = [$this->_content];
                }

                $message_id = $message?->message_id ?? \UUID::v4();

                $mPush
                    ->db()
                    ->replace($mPush->table('messages'), [
                        'message_id' => $message_id,
                        'member_id' => $this->_member_id,
                        'component_type' => $this->_component->getType(),
                        'component_name' => $this->_component->getName(),
                        'target_type' => $this->_target_type,
                        'target_id' => $this->_target_id,
                        'code' => $this->_code,
                        'contents' => \Format::toJson($contents),
                        'sended_by' => $this->_sended_by,
                        'sended_at' => $sended_at,
                        'is_checked' => 'FALSE',
                        'is_readed' => 'FALSE',
                        'type' => $this->_type,
                    ])
                    ->execute();
            }

            if ($this->_cellphone !== null && $channel == 'SMS' && \Modules::isInstalled('sms') == true) {
                $mPush
                    ->getProtocol($this->_component)
                    ->getSMS($this->getMessage(), $this->_name, $this->_cellphone)
                    ?->send();
            }

            if ($this->_email !== null && $channel == 'EMAIL' && \Modules::isInstalled('email') == true) {
                $mPush
                    ->getProtocol($this->_component)
                    ->getEmail($this->getMessage(), $this->_name, $this->_email)
                    ?->send();
            }
        }

        return true;
    }
}
