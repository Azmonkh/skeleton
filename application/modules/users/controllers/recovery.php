<?php
/**
 * Initialization password recovery
 *
 * @category Application
 *
 * @author   Anton Shevchuk
 * @created  11.12.12 13:03
 */
namespace Application;

use Application\Users;
use Bluz\Proxy\Logger;
use Bluz\Proxy\Mailer;
use Bluz\Proxy\Messages;
use Bluz\Proxy\Request;
use Bluz\Proxy\Router;

return
/**
 * @param string $email
 * @return \closure
 */
function ($email = null) use ($view) {
    /**
     * @var Bootstrap $this
     * @var \Bluz\View\View $view
     */
    // change layout
    $this->useLayout('small.phtml');

    if (Request::isPost()) {
        try {
            // check email
            if (empty($email)) {
                throw new Exception('Email can\'t be empty');
            }
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                list(, $domain) = explode("@", $email, 2);
                if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
                    throw new Exception('Email has invalid domain name');
                }
            } else {
                throw new Exception('Email is invalid');
            }
            // check exists
            $user = Users\Table::findRowWhere(['email' => $email]);
            if (!$user) {
                throw new Exception('Email not found');
            }
            // check status, only for active users
            if ($user->status != Users\Table::STATUS_ACTIVE) {
                throw new Exception('User is inactive');
            }

            // create activation token
            // valid for 5 days
            $actionRow = UsersActions\Table::getInstance()->generate($user->id, UsersActions\Table::ACTION_RECOVERY, 5);

            // send activation email
            // generate restore URL
            $resetUrl = Router::getFullUrl(
                'users',
                'recovery-reset',
                ['code' => $actionRow->code, 'id' => $user->id]
            );

            $subject = "Password Recovery";

            $body = $this->dispatch(
                'users',
                'mail-template',
                [
                    'template' => 'recovery',
                    'vars' => ['user' => $user, 'resetUrl' => $resetUrl]
                ]
            )->render();

            try {
                $mail = Mailer::create();

                // subject
                $mail->Subject = $subject;
                $mail->MsgHTML(nl2br($body));

                $mail->AddAddress($user->email);

                Mailer::send($mail);

            } catch (\Exception $e) {
                // log it
                Logger::log(
                    'error',
                    $e->getMessage(),
                    ['module' => 'users', 'controller' => 'recovery', 'email' => $email]
                );
                throw new Exception('Unable to send email. Please contact administrator.');
            }

            // show notification and redirect
            Messages::addSuccess(
                "Reset password instructions has been sent to your email address"
            );
            $this->redirectTo('index', 'index');

        } catch (Exception $e) {
            Messages::addError($e->getMessage());
        }
        $view->email = $email;
    }
};
