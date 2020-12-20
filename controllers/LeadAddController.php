<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\db\Exception;

class LeadAddController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Displays homepage.
     *
     * @return string
     * @throws HttpException
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        
        if (! $request->isPost) {
            throw new HttpException(400,'Допустимый метод запроса POST');
        }

        foreach ($_POST["leads"]["add"] as $lead) {
            $lead_id = $lead['id'];

            // Проверяем наличие кастомного поля 1
            if (! isset($lead['custom_fields'][0]['values'][0]['value'])) {
                return false;
            }

            $custom_field_1 = $lead['custom_fields'][0]['values'][0]['value'];
            $custom_field_2 = $custom_field_1 * 2;

            $subdomain = Yii::$app->params['subdomain']; // Поддомен нужного аккаунта

            $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads'; //Формируем URL для запроса
            /** Получаем access_token из вашего хранилища */
            $access_token = Yii::$app->params['access_token'];
            /** Формируем заголовки */
            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ];

            $data = '[{"id": ' . $lead_id . ' , "custom_fields_values": [{"field_id": 244583, "values": [{"value": "' . $custom_field_2 . '"}]}]}]';

            /**
             * Нам необходимо инициировать запрос к серверу.
             * Воспользуемся библиотекой cURL (поставляется в составе PHP).
             * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
             */
            $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
            /** Устанавливаем необходимые опции для сеанса cURL  */
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
            curl_setopt($curl,CURLOPT_URL, $link);
            curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl,CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
            $code = (int)$code;
            $errors = [
                400 => 'Bad request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not found',
                500 => 'Internal server error',
                502 => 'Bad gateway',
                503 => 'Service unavailable',
            ];

            try
            {
                /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
                if ($code < 200 || $code > 204) {
                    throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
                }
            }
            catch(\Exception $e)
            {
                die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
            }
        }

        die();
    }
}
