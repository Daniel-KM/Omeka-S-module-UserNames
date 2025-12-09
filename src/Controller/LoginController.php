<?php

namespace UserNames\Controller;

use UserNames\Form\LoginForm;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container as SessionContainer;

class LoginController extends \Omeka\Controller\LoginController
{
    public function loginAction()
    {
        if ($this->auth->hasIdentity()) {
            return $this->userIsAllowed('Omeka\Controller\Admin\Index', 'browse')
                ? $this->redirect()->toRoute('admin')
                : $this->redirect()->toRoute('top');
        }

        $form = $this->getForm(LoginForm::class);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                // Create a new session, avoiding the warning in case of error.
                // Avoid warning: session_regenerate_id(): Session object destruction
                // failed when session save handler has issues.
                // See /vendor/laminas/laminas-session/src/SessionManager.php on line 337.
                $sessionManager = SessionContainer::getDefaultManager();
                @$sessionManager->regenerateId();
                $validatedData = $form->getData();
                $adapter = $this->auth->getAdapter();
                $adapter->setIdentity($validatedData['email']);
                $adapter->setCredential($validatedData['password']);
                $result = $this->auth->authenticate();
                if ($result->isValid()) {
                    $this->messenger()->addSuccess('Successfully logged in'); // @translate
                    $eventManager = $this->getEventManager();
                    $eventManager->trigger('user.login', $this->auth->getIdentity());
                    $session = $sessionManager->getStorage();
                    if ($redirectUrl = $session->offsetGet('redirect_url')) {
                        return $this->redirect()->toUrl($redirectUrl);
                    }
                    return $this->userIsAllowed('Omeka\Controller\Admin\Index', 'browse')
                        ? $this->redirect()->toRoute('admin')
                        : $this->redirect()->toRoute('top');
                } else {
                    $this->messenger()->addError('User name, email, or password is invalid'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setTemplate('omeka/login/login');
        $view->setVariable('form', $form);
        return $view;
    }
}
