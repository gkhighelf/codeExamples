<?
/**
 * Yandex Translate API
 *
 * Works with yandex api to translate text
 *
 * @author Konstantin Gritsenko <gkhighelf@gmail.com>
 * @version 0.1.0
 */
class YandexTranslator extends ApiBase {
    /**
     * Api call results
     */
    const RESULT_ERROR_WRONG_API_KEY = "401";
    const RESULT_ERROR_API_KEY_BLOCKED = "402";
    const RESULT_ERROR_REQUEST_DAILY_LIMIT_REACHED = "403";
    const RESULT_ERROR_DAILY_DATASIZE_LIMIT_REACHED = "404";
    const RESULT_ERROR_TEXT_MAXSIZE_REACHED = "413";
    const RESULT_ERROR_ERROR_TRANSLATING_TEXT = "422";
    const RESULT_ERROR_WRONG_LANGUAGE_PAIR = "501";

/**
200 "Операция выполнена успешно"
401 "Неправильный ключ API"
402 "Ключ API заблокирован"
403 "Превышено суточное ограничение на количество запросов"
404 "Превышено суточное ограничение на объем переведенного текста"
413 "Превышен максимально допустимый размер текста"
422 "Текст не может быть переведен"
501 "Заданное направление перевода не поддерживается"
*/

    /**
    * Current server API version, used to build server api url
    * 
    * @var string
    */
    protected $_api_version = "v1.5";

    /** @var string */
    private $_default_lang_pair = "uk-ru";

    /** Возвращаем базовую ссылку на апи */
    protected function getBaseAPIUrl() {
        return "https://translate.yandex.net/api";
    }

    /**
    * Translating text with language pairs.
    * 
    * @param string $text
    * @param string $lang_pair
    */
    public function translate( $text, $lang_pair = "uk-ru" )
    {
        return parent::translate( array(
            "text" => $text,
            "lang" => $lang_pair
        ));
    }
}
?>