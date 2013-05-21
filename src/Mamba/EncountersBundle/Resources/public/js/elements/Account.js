/**
 * Account
 *
 * @author shpizel
 */
$Account = {

    /**
     * Инициализация интерфейса элемента
     *
     * @init UI
     */
    initUI: function($route) {
        $(".info-meet li.item-account div.bar div.account").click(function() {
            $Layers.showAccountLayer();
            return false;
        });
    },

    /**
     * Устанавливает баланс в интерфейсе и конфиге
     *
     * @param $account
     */
    setAccount: function($account) {
        $(".info-meet li.item-account div.account span").html($account + "<i></i>");
        $Config.get('webuser')['account'] = $account;
    },

    /**
     * Account getter from $Config
     *
     * @return int
     */
    getAccount: function() {
        return $Config.get('webuser')['account'];
    }
}