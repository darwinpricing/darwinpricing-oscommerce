<?php

/**
 * 2015 Darwin Pricing
 *
 * For support please visit www.darwinpricing.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/lgpl.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@darwinpricing.com so we can send you a copy immediately.
 *
 *  @author    Darwin Pricing <support@darwinpricing.com>
 *  @copyright 2015 Darwin Pricing
 *  @license   http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License (LGPL 3.0)
 */
class ht_darwinpricing {

    public $code = 'ht_darwinpricing';
    public $group = 'header_tags';
    public $title;
    public $description;
    public $sort_order;
    public $enabled = false;

    function __construct() {
        $this->title = MODULE_HEADER_TAGS_DARWINPRICING_TITLE;
        $this->description = MODULE_HEADER_TAGS_DARWINPRICING_DESCRIPTION;

        if ($this->check()) {
            $this->sort_order = MODULE_HEADER_TAGS_DARWINPRICING_SORT_ORDER;
            $liveMode = ('True' === MODULE_HEADER_TAGS_DARWINPRICING_LIVE_MODE);
            $serverUrl = tep_output_string_protected(MODULE_HEADER_TAGS_DARWINPRICING_SERVER_URL);
            $clientId = (int) MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_ID;
            $clientSecret = tep_output_string_protected(MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_SECRET);
            $this->enabled = ($liveMode && strlen($serverUrl) && ($clientId > 0) && strlen($clientSecret));
        }
    }

    public function check() {
        return defined('MODULE_HEADER_TAGS_DARWINPRICING_LIVE_MODE');
    }

    public function execute() {
        global $PHP_SELF, $oscTemplate, $customer_id;
        if (!$this->isEnabled()) {
            return;
        }
        if (($PHP_SELF == FILENAME_CHECKOUT_SUCCESS) && tep_session_is_registered('customer_id')) {
            $this->trackOrder($customer_id);
        } else {
            $widgetUrl = json_encode($this->getApiUrl('/widget'));
            $output = <<<EOD
<script>
(function(s, n) {
    s = document.createElement('script');
    s.async = 1;
    s.src = {$widgetUrl};
    n = document.getElementsByTagName('script')[0];
    n.parentNode.insertBefore(s, n);
})();
</script>
EOD;
            $oscTemplate->addBlock($output, $this->group);
        }
    }

    public function install() {
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Live mode', 'MODULE_HEADER_TAGS_DARWINPRICING_LIVE_MODE', 'True', 'Use this module in live mode', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('API Server', 'MODULE_HEADER_TAGS_DARWINPRICING_SERVER_URL', '', 'The URL of the Darwin Pricing API server for your website', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Client ID', 'MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_ID', '', 'The client ID for your website', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Client Secret', 'MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_SECRET', '', 'The client secret for your website', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Sort Order', 'MODULE_HEADER_TAGS_DARWINPRICING_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function keys() {
        return array('MODULE_HEADER_TAGS_DARWINPRICING_LIVE_MODE', 'MODULE_HEADER_TAGS_DARWINPRICING_SERVER_URL', 'MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_ID', 'MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_SECRET', 'MODULE_HEADER_TAGS_DARWINPRICING_SORT_ORDER');
    }

    public function remove() {
        tep_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * @param string $path
     * @param bool $authenticationRequired
     * @return string
     */
    protected function getApiUrl($path, $authenticationRequired = null) {
        $serverUrl = tep_output_string_protected(MODULE_HEADER_TAGS_DARWINPRICING_SERVER_URL);
        $clientId = (int) MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_ID;
        $clientSecret = tep_output_string_protected(MODULE_HEADER_TAGS_DARWINPRICING_CLIENT_SECRET);
        $serverUrl = rtrim($serverUrl, '/');
        $apiUrl = $serverUrl . $path;
        $parameterList = array('platform' => 'oscommerce-' . tep_get_version(), 'site-id' => $clientId);
        if ($authenticationRequired) {
            $parameterList['hash'] = $clientSecret;
            $parameterList['visitor-ip'] = $this->getRemoteIp();
        }
        $apiUrl .= '?' . http_build_query($parameterList);
        return $apiUrl;
    }

    /**
     * @param int $customerId
     * @return array|null
     */
    protected function getOrderLast($customerId) {
        $customerId = (int) $customerId;
        $query = tep_db_query("SELECT * FROM " . TABLE_ORDERS . " WHERE customers_id = " . $customerId . " ORDER BY date_purchased DESC LIMIT 1");
        if (1 === tep_db_num_rows($query)) {
            return tep_db_fetch_array($query);
        }
        return null;
    }

    /**
     * @return string
     */
    protected function getRemoteIp() {
        if (function_exists('tep_get_ip_address')) {
            $remoteIp = tep_get_ip_address();
            if (isset($remoteIp) && '0.0.0.0' !== $remoteIp) {
                return $remoteIp;
            }
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $remoteIp = (string) $_SERVER['REMOTE_ADDR'];
        } else {
            return '';
        }
        if (isset($_SERVER['SERVER_ADDR'])) {
            $serverIp = (string) $_SERVER['SERVER_ADDR'];
            if ($remoteIp === $serverIp) {
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $proxyIps = (string) $_SERVER['HTTP_X_FORWARDED_FOR'];
                    $proxyIpList = preg_split('#\\s*+,\\s*+#', trim($proxyIps));
                    array_reverse($proxyIpList);
                    foreach ($proxyIpList as $proxyIp) {
                        if ($serverIp !== $proxyIp) {
                            return $proxyIp;
                        }
                    }
                }
                return '';
            }
        }
        return $remoteIp;
    }

    /**
     * @param int $customerId
     */
    protected function trackOrder($customerId) {
        try {
            if ($this->isEnabled()) {
                $order = $this->getOrderLast($customerId);
                if (is_array($order)) {
                    $url = $this->getApiUrl('/oscommerce/webhook-order', true);
                    $body = json_encode($order);
                    $this->webhook($url, $body);
                }
            }
        } catch (Exception $exception) {
            
        }
    }

    /**
     * @param string $url
     * @param string $body
     */
    protected function webhook($url, $body) {
        $optionList = array(
            CURLOPT_POST => true,
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 3000,
            CURLOPT_POSTFIELDS => $body,
        );
        $ch = curl_init();
        curl_setopt_array($ch, $optionList);
        curl_exec($ch);
        curl_close($ch);
    }

}
