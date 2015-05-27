<?php
require_once dirname(__FILE__) . '/Components/Barzahlen/Api/loader.php';

/**
 * Barzahlen Payment Module (Shopware 3.5)
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; version 3 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/
 *
 * @copyright   Copyright (c) 2012 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/AGPL-3.0  GNU Affero General Public License, version 3 (GPL-3.0)
 */

class Shopware_Plugins_Frontend_ZerintPaymentBarzahlenSW3_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    const LOGFILE = 'files/log/barzahlen.log';

    /**
     * Install methods. Calls sub methods for a successful installation.
     *
     * @return boolean
     */
    public function install()
    {
        $this->createEvents();
        $this->createPayment();
        $this->createRules();
        $this->createForm();

        return true;
    }

    /**
     * Subscribes to events in order to run plugin code.
     */
    protected function createEvents()
    {
        $event = $this->createEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentBarzahlen', 'onGetControllerPathFrontend');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Action_Frontend_PaymentBarzahlen_Notify', 'onNotification');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Action_Frontend_Checkout_Finish', 'onCheckoutSuccess');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Action_Frontend_Account_Payment', 'onSelectPaymentMethod');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Action_Frontend_Checkout_Confirm', 'onCheckoutConfirm');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Action_PostDispatch_Backend_Index', 'onBackendIndexPostDispatch');
        $this->subscribeEvent($event);
    }

    /**
     * Creates a new or updates the old payment entry for the database.
     */
    public function createPayment()
    {
        $getOldPayments = $this->Payment();

        if (empty($getOldPayments['id'])) {
            $settings = array('name' => 'barzahlen',
                'description' => 'Barzahlen',
                'action' => 'payment_barzahlen',
                'active' => 0,
                'position' => 1,
                'pluginID' => $this->getId());

            Shopware()->Payments()->createRow($settings)->save();
        }
    }

    /**
     * Sets rules for Barzahlen payment.
     * Country = DE
     * max. Order Amount < 1000 Euros
     */
    public function createRules()
    {
        $payment = $this->Payment();

        $rules = "INSERT INTO s_core_rulesets
              (paymentID, rule1, value1)
              VALUES
              ('" . (int) $payment['id'] . "', 'ORDERVALUEMORE', '1000'),
              ('" . (int) $payment['id'] . "', 'LANDISNOT', 'DE'),
              ('" . (int) $payment['id'] . "', 'CURRENCIESISOISNOT', 'EUR')";

        Shopware()->Db()->query($rules);
    }

    /**
     * Creates the settings form for the backend.
     */
    protected function createForm()
    {
        $form = $this->Form();

        $form->setElement('checkbox', 'barzahlenSandbox', array(
            'label' => 'Testmodus',
            'value' => true,
            'required' => true
        ));

        $form->setElement('text', 'barzahlenShopId', array(
            'label' => 'Shop ID',
            'value' => '',
            'required' => true
        ));

        $form->setElement('text', 'barzahlenPaymentKey', array(
            'label' => 'Zahlungsschl&uuml;ssel',
            'value' => '',
            'required' => true
        ));

        $form->setElement('text', 'barzahlenNotificationKey', array(
            'label' => 'Benachrichtigungsschl&uuml;ssel',
            'value' => '',
            'required' => true
        ));

        $form->setElement('checkbox', 'barzahlenDebug', array(
            'label' => 'Erweitertes Logging',
            'value' => false,
            'required' => true
        ));
        $form->save();
    }

    /**
     * Performs the uninstallation of the payment plugin.
     *
     * @return boolean
     */
    public function uninstall()
    {
        $payment = $this->Payment();
        Shopware()->Db()->query("DELETE FROM s_core_rulesets WHERE paymentID = '" . (int) $payment['id'] . "'");
        $this->disable();

        return true;
    }

    /**
     * Enables the payment method.
     *
     * @return parent return
     */
    public function enable()
    {
        $payment = $this->Payment();
        $payment->active = 1;
        $payment->save();

        return parent::enable();
    }

    /**
     * Disables the payment method.
     *
     * @return parent return
     */
    public function disable()
    {
        $payment = $this->Payment();
        $payment->active = 0;
        $payment->save();

        return parent::disable();
    }

    /**
     * Gathers all information for the backend overview of the plugin.
     *
     * @return array with all information
     */
    public function getInfo()
    {
        $img = 'https://cdn.barzahlen.de/images/barzahlen_logo.png';
        return array(
            'version' => $this->getVersion(),
            'autor' => 'Zerebro Internet GmbH',
            'label' => "Barzahlen Payment Module",
            'source' => "Local",
            'description' => '<p><img src="' . $img . '" alt="Barzahlen" /></p> <p>Barzahlen bietet Ihren Kunden die M&ouml;glichkeit, online bar zu bezahlen. Sie werden in Echtzeit &uuml;ber die Zahlung benachrichtigt und profitieren von voller Zahlungsgarantie und neuen Kundengruppen. Sehen Sie wie Barzahlen funktioniert: <a href="http://www.barzahlen.de/partner/funktionsweise" target="_blank">http://www.barzahlen.de/partner/funktionsweise</a></p><p>Sie haben noch keinen Barzahlen-Account? Melden Sie sich hier an: <a href="https://partner.barzahlen.de/user/register" target="_blank">https://partner.barzahlen.de/user/register</a></p>',
            'license' => 'GNU GPL v3.0',
            'copyright' => 'Copyright (c) 2013, Zerebro Internet GmbH',
            'support' => 'support@barzahlen.de',
            'link' => 'http://www.barzahlen.de'
        );
    }

    /**
     * Returns the currennt plugin version.
     *
     * @return string with current version
     */
    public function getVersion()
    {
        return "1.0.3";
    }

    /**
     * Selects all payment method information from the database.
     *
     * @return payment method information
     */
    public function Payment()
    {
        return Shopware()->Payments()->fetchRow(array('name=?' => 'barzahlen'));
    }

    /**
     * Calls the payment constructor when frontend event fires.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string with path to payment controller
     */
    public static function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        return dirname(__FILE__) . '/Controllers/Frontend/PaymentBarzahlen.php';
    }

    /**
     * Sets empty template file to avoid errors.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public static function onNotification(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        $view->extendsTemplate('frontend/payment_barzahlen/notify.tpl');
    }

    /**
     * Prepares checkout success page with received payment slip information.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public static function onCheckoutSuccess(Enlight_Event_EventArgs $args)
    {
        if (isset(Shopware()->Session()->BarzahlenResponse)) {
            $view = $args->getSubject()->View();
            Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
            $view->barzahlen_infotext_1 = Shopware()->Session()->BarzahlenResponse['infotext-1'];
            $view->extendsBlock(
                'frontend_checkout_finish_teaser',
                '{include file="frontend/payment_barzahlen/infotext.tpl"}' . "\n",
                'prepend'
            );
        }
    }

    /**
     * Setting payment method selection payment description depending on sandbox
     * settings in payment config.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public static function onSelectPaymentMethod(Enlight_Event_EventArgs $args)
    {
        $payment = Shopware()->Payments()->fetchRow(array('name=?' => 'barzahlen'));
        $config = Shopware()->Plugins()->Frontend()->ZerintPaymentBarzahlenSW3()->Config();

        $description = '<img src="https://cdn.barzahlen.de/images/barzahlen_logo.png" style="height: 45px;"/><br/>';
        $description .= '<p id="payment_desc"><img src="https://cdn.barzahlen.de/images/barzahlen_special.png" style="float: right; margin-left: 10px; max-width: 180px; max-height: 180px;">Mit Abschluss der Bestellung bekommen Sie einen Zahlschein angezeigt, den Sie sich ausdrucken oder auf Ihr Handy schicken lassen k&ouml;nnen. Bezahlen Sie den Online-Einkauf mit Hilfe des Zahlscheins an der Kasse einer Barzahlen-Partnerfiliale.';

        if ($config->barzahlenSandbox) {
            $description .= '<br/><br/>Der <strong>Sandbox Modus</strong> ist aktiv. Allen get&auml;tigten Zahlungen wird ein Test-Zahlschein zugewiesen. Dieser kann nicht von unseren Einzelhandelspartnern verarbeitet werden.';
        }

        $description .= '</p>';
        $description .= '<b>Bezahlen Sie bei:</b>&nbsp;';

        for ($i = 1; $i <= 10; $i++) {
            $count = str_pad($i, 2, "0", STR_PAD_LEFT);
            $description .= '<img src="https://cdn.barzahlen.de/images/barzahlen_partner_' . $count . '.png" alt="" style="height: 1em; vertical-align: -0.1em;" />';
        }

        $newData = array('additionaldescription' => $description);
        $where = array('id = ' . (int) $payment['id']);

        Shopware()->Payments()->update($newData, $where);
    }

    /**
     * Checks for plugin updates. (Once a week.)
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendIndexPostDispatch(Enlight_Event_EventArgs $args)
    {
        if (file_exists('files/log/barzahlen.check')) {
            $file = fopen('files/log/barzahlen.check', 'r');
            $lastCheck = fread($file, 1024);
            fclose($file);
        } else {
            $lastCheck = 0;
        }

        if (Shopware()->Auth()->hasIdentity() && ($lastCheck == 0 || $lastCheck < strtotime("-1 week"))) {

            if(!file_exists('files/log/')) {
                if(!mkdir('files/log/')) {
                    return;
                }
            }

            $file = fopen('files/log/barzahlen.check', 'w');
            fwrite($file, time());
            fclose($file);

            try {
                $config = Shopware()->Plugins()->Frontend()->ZerintPaymentBarzahlenSW3()->Config();
                $shopId = $config->barzahlenShopId;
                $paymentKey = $config->barzahlenPaymentKey;

                $checker = new Barzahlen_Version_Check($shopId, $paymentKey);
                $response = $checker->checkVersion('Shopware 3', '3.5.6', Shopware()->Plugins()->Frontend()->ZerintPaymentBarzahlenSW3()->getVersion());

                if ($response != false) {
                    echo '<script type="text/javascript">
                          if(confirm(unescape("F%FCr das Barzahlen-Plugin ist eine neue Version (' . (string) $response . ') verf%FCgbar. Jetzt ansehen?"))) {
                            window.location.href = "http://www.barzahlen.de/partner/integration/shopsysteme/12/shopware";
                          }</script>';
                }
            } catch (Exception $e) {
                $this->_logError($e);
            }
        }
    }

    /**
     * Extending checkout/confirm template to show Barzahlen Payment Error, if
     * necessary.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public static function onCheckoutConfirm(Enlight_Event_EventArgs $args)
    {
        if (isset(Shopware()->Session()->BarzahlenPaymentError)) {
            $view = $args->getSubject()->View();
            Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
            $view->barzahlen_payment_error = Shopware()->Session()->BarzahlenPaymentError;
            $view->extendsTemplate('frontend/payment_barzahlen/error.tpl');
            unset(Shopware()->Session()->BarzahlenPaymentError);
        }
    }

    /**
     * Saves errors to given log file.
     *
     * @param string $error error message
     */
    protected function _logError($error)
    {
        $time = date("[Y-m-d H:i:s] ");
        error_log($time . $error . "\r\r", 3, self::LOGFILE);
    }
}
