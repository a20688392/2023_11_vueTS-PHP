<?php

###################
# 使用者的處理函式
# Jone 2022-07
###################

namespace App\Model;

use App\Config\Database;
use PDO;
use Exception;
use PDOException;

class User
{
    /**
     * 進行與資料庫的初始連線
     * 回傳連線
     *
     * @return  PDO         $db     資料庫的連線
     * @throws  Exception   $e      回應錯誤訊息
     */
    public function dbConnect()
    {
        $db_type = Database::DATABASE_INFO['db_type'];
        $db_host = Database::DATABASE_INFO['db_host'];
        $db_name = Database::DATABASE_INFO['db_name'];
        $db_user = Database::DATABASE_INFO['db_user'];
        $db_pass = Database::DATABASE_INFO['db_pass'];
        $connect = $db_type . ":host=" . $db_host . ";dbname=" . $db_name;
        try {
            $db = new PDO($connect, $db_user, $db_pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->query("SET NAMES UTF8");
        } catch (PDOException $e) {
            die("Error!:" . $e->getMessage() . '<br>');
        }
        return $db;
    }
    /**
     * 檢查信箱、使用者是否已註冊過
     *
     * @param   string  $account    使用者名
     * @param   string  $email      使用者信箱
     * @return  array
     * name_RESULT      為0 時，代表沒有被註冊過，1為有
     * email_RESULT     為0 時，代表沒有被註冊過，1為有
     */
    public function checkEmailName(string $account, string $email)
    {
        $db = $this->dbConnect();
        $sql = "SELECT IF( EXISTS(
                            SELECT account
                            FROM users
                            WHERE account = ?), 1, 0) as name_RESULT,
                        IF( EXISTS(
                            SELECT email
                            FROM users
                            WHERE email = ?), 1, 0) as email_RESULT;";
        $statement = $db->prepare($sql);
        $statement->execute([$account, $email]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * 註冊使用者
     *
     * @param   string  $account      使用者名
     * @param   string  $email     使用者信箱
     * @param   string  $pass  使用者密碼
     *
     * @throws  Exception   $e          回應錯誤訊息
     *
     * $account、$email、$pass 之一未填
     * 回傳 "有欄位未填"
     *
     * $email FILTER_SANITIZE_EMAIL、FILTER_VALIDATE_EMAIL
     * 為true，代表信箱格是不合規定，
     * 回傳 "信箱格式錯誤" . "<br>" . "信箱範例：test@example.com"
     *
     * name_RESULT      為0 時，代表沒有被註冊過，1為有
     * 回傳 "使用者名已被註冊"
     *
     * email_RESULT     為0 時，代表沒有被註冊過，1為有
     * 回傳 "信箱已被註冊"
     * 同時都有回傳 "使用者名和信箱已被註冊"
     *
     * @return  array       $return     將回傳的 API 回應資訊，回傳成功 *                                  或者失敗
     */
    public function addUser(string $account, string $email, string $pass)
    {
        $db = $this->dbConnect();
        $sql = "INSERT INTO `users`(`account`, `email`, `password`) VALUES (?,?,?)";
        $statement = $db->prepare($sql);
        $pass = password_hash($pass, PASSWORD_DEFAULT);
        $check = $this->checkEmailName($account, $email);
        $return = [];

        try {
            if (empty($account) || empty($pass) || empty($email)) {
                throw new Exception("有欄位未填");
                //信箱
                //把值作為電子郵件地址來驗證
                //過濾允許所有的字母、數字以及$-_.+!*'{}|^~[]`#%/?@&=
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($email, FILTER_SANITIZE_EMAIL)) {
                throw new Exception("信箱格式錯誤" . "<br>" . "信箱範例：test@example.com");
            } elseif ($check['name_RESULT'] || $check['email_RESULT']) {
                if (($check['name_RESULT'] && $check['email_RESULT'])) {
                    throw new Exception("使用者名和信箱已被註冊");
                } elseif ($check['name_RESULT']) {
                    throw new Exception("使用者名已被註冊");
                } else {
                    throw new Exception("信箱已被註冊");
                }
            } elseif ($statement->execute([$account, $email, $pass])) {
                $return = [
                    "event" => "註冊訊息",
                    "status" => "success",
                    "content" => "註冊成功",
                ];
            } else {
                throw new Exception("未知錯誤" . $statement->errorInfo()[2]);
            }
        } catch (PDOException $e) {
            $return = [
                "event" => "註冊訊息",
                "status" => "error",
                "content" => "註冊失敗" . $e->getMessage(),
            ];
            http_response_code(500);
            return $return;
        } catch (Exception $e) {
            $return = [
                "event" => "註冊訊息",
                "status" => "error",
                "content" => "註冊失敗，" . $e->getMessage(),
            ];
            http_response_code(400);
            return $return;
        }
        http_response_code(201);
        return $return;
    }
}
