<?php
namespace Model;

include_once "database.php";
use \DB\database;

function create_database()
{
    global $log;
    $log->info("start create");
    try {
        $db = Database::Instance();
        $db->exec("Drop table if exists car");
        $str= "CREATE TABLE car (
     id SERIAL,
     user_id int,
     name varchar(40),
     license varchar(40))";
        $db->exec($str);
        $db->exec("Drop table if exists user_account");
        $str= "CREATE TABLE user_account (
     id SERIAL,
     email varchar(40),
     password varchar(40),
     token varchar(250))";
        $db->exec($str);
        $db->exec("Drop table if exists contact");
        $str= "CREATE TABLE contact (
     id SERIAL,
     user_id int,
     name varchar(40),
     street varchar(40),
     country varchar(40),
     zip varchar(40),
     place varchar(40),
     is_deleted boolean)";
        $db->exec($str);
        $db->exec("Drop table if exists ride");
        $str= "CREATE TABLE ride (
     id SERIAL,
     create_date timestamp default current_timestamp,
     ride_date date,
     car_id int,
     isBusiness boolean,
     contact_id int,
     km int,
     comment varchar(255))";
        $db->exec($str);
        $log->info("done create");
    } catch (Exception $e) {
        $error = $e->getMessage();
        $log->info($error);
    }
}

class User
{
    private $logger = null;
    private $db = null;

    public function __construct()
    {
        global $log;
        $this->logger = $log;
        // open database connection
        $this->db = Database::Instance();
    }
    public function __destruct()
    {
        // close the database connection
        $this->db = null;
    }
    public function create($email, $password)
    {
        $str = "insert into user_account (email, password) values"
                . "(:email, "
                . ":password) ";
        $values = array(":email" => $email, ":password" => $password);

        try {
            return $this->db->insert($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function update($id, $email, $password)
    {
        $str = "update user_account set email = :email,  password = :password"
                  . " where id = :id ";
        $values = array(":email" => $email, ":password" => $password, ":id" => $id);
        try {
            return $this->db->update($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function api_login($email, $password)
    {
        $str = "select id from user_account"
                  . " where email = :email and password = :password  ";
        $values = array(":email" => $email, ":password" => $password);
        try {
            $arr = $this->db->select($str, $values);
            if (sizeof($arr) != 0) {
                $id = $arr[0]['id'];
                $token = '';
                $token = $this->create_token($id);
            } else {
                $id=0;
                $token='';
            }
            $arr = array("id" => $id, "token" => $token );
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function create_token($id)
    {
        $length = 50;
        $token = bin2hex(random_bytes($length));
        $str = "update user_account set token = :token"
                 . " where id = :id ";
        $values = array(":token" => $token,  ":id" => $id);
        try {
            $this->db->update($str, $values);
            return $token;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function get_token($email)
    {
        $str = "select token , id from user_account "
                 . " where email = :email ";
        $values = array(":email" => $email);
        try {
            $arr = $this->db->select($str, $values);
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}

class Car
{
    private $logger = null;
    private $db = null;

    public function __construct()
    {
        global $log;
        $this->logger = $log;
        // open database connection
        $this->db = Database::Instance();
    }
    public function __destruct()
    {
        // close the database connection
        $this->db = null;
    }
    public function create($user_id, $name, $license)
    {
        $str = "insert into car (user_id, name, license) values"
                . "(:user_id,:name, :license) ";
        $values = array(":user_id" => $user_id,":name" => $name, ":license" => $license);
        try {
            return $this->db->insert($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function update($user_id, $car_id, $name, $license)
    {
        $str = "update car set license = :license, name = :name"
                  . " where id = :id  and user_id = :user_id";
        $values = array(":user_id" => $user_id, ":license" => $license, ":name" => $name,":id" => $car_id);
        try {
            return $this->db->update($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}


class Contact
{
    private $logger = null;
    private $db = null;

    public function __construct()
    {
        global $log;
        $this->logger = $log;
        // open database connection
        $this->db = Database::Instance();
    }
    public function __destruct()
    {
        // close the database connection
        $this->db = null;
    }
    public function create($user_id, $name, $street, $place, $zip, $country)
    {
        $str = "insert into contact (user_id, name,street, place, zip, country, is_deleted) values"
                . "(:user_id, :name,:street, :place, :zip, :country, FALSE)";
        $values = array(":user_id" => $user_id,":name" => $name,":street" => $street,":place" => $place,":zip" => $zip,":country" => $country);
        try {
            return $this->db->insert($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function update($user_id, $id, $name, $street, $place, $zip, $country)
    {
        $this->logger->info("update");
        $str = "update contact set name = :name,"
                  . "street = :street, place = :place, zip = :zip, country = :country"
                  . " where id = :id and user_id = :user_id";
        $values = array(":user_id" => $user_id, ":id" => $id,":name" => $name,":street" => $street,":place" => $place,":zip" => $zip,":country" => $country);

        try {
            return $this->db->update($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function delete($user_id, $id)
    {
        $this->logger->info("delete");
        $str = "update contact set is_deleted = TRUE"
                   . " where is_deleted = FALSE and id = :id and  "
                  ." user_id = :user_id";
        $values = array(":id" => $id, ":user_id" => $user_id);
        try {
            $this->db->delete($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}

class Ride
{
    private $logger = null;
    private $db = null;

    public function __construct()
    {
        global $log;
        $this->logger = $log;
        // open database connection
        $this->db = Database::Instance();
    }
    public function __destruct()
    {
        // close the database connection
        $this->db = null;
    }


    public function create($user_id, $car_id, $ride_date, $isBusiness, $km, $contact_id, $comment)
    {
        //      Add select to see if car is part of user
        $str = "insert into ride (car_id, isBusiness, ride_date, km,contact_id, comment) values"
                . "(:car_id,  :isBusiness, :ride_date, :km,:contact_id,  :comment)";
        $values = array(":car_id" => $car_id, ":isBusiness" => $isBusiness, ":ride_date" => $ride_date,":km" => $km,":contact_id" => $contact_id,   ":comment" => $comment );
        try {
            return $this->db->insert($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function update($user_id, $car_id, $ride_id, $ride_date, $isBusiness, $km, $contact_id, $comment)
    {
        $str = "update ride set isBusiness = :isBusiness, ride_date = :ride_date, km = :km, "
                  . "contact_id = :contact_id,  comment = :comment"
                  . " where id = :ride_id  and car_id = :car_id and car_id in  "
                  . " (select car_id from car where user_id = :user_id)";
        $values = array(":user_id" => $user_id, ":ride_id" => $ride_id,":car_id" => $car_id, ":isBusiness" => $isBusiness, ":ride_date" => $ride_date,":km" => $km,":contact_id" => $contact_id,  ":comment" => $comment);
        try {
            return $this->db->update($str, $values);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}

class Report
{
    private $logger = null;
    private $db = null;

    public function __construct()
    {
        global $log;
        $this->logger = $log;
        // open database connection
        $this->db = Database::Instance();
    }
    public function __destruct()
    {
        // close the database connection
        $this->db = null;
    }
    public function list_car($user_id, $car_id)
    {
        $str = "select car.id as id, car.name as name,car.license as licence, user_account.id as user_account_id  "
              . " from car, user_account where car.user_id = :user_account_id and car.id = :car_id";
        $values = array(":car_id" => $car_id, ":user_id" => $user_id);
        try {
            $arr = $this->db->select($str, $values);
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function list_contacts($user_id)
    {
        $str = "select * from contact "
              . " where is_deleted = FALSE and user_id = :user_id";
        $values = array(":user_id" => $user_id);
        try {
            $arr = $this->db->select($str, $values);
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
    public function list_cars($user_id)
    {
        $str = "select * from car "
              . " where  user_id = :user_id";
        $values = array(":user_id" => $user_id);
        try {
            $arr = $this->db->select($str, $values);
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function list_rides($user_id, $car_id, $offset, $limit)
    {
        $str =  "select ride.id as id,  ride.isBusiness as isBusiness, to_char( ride.ride_date , 'YYYY-MM-DD') as ride_date, "
          . "ride.km as km,   "
          . "ride.contact_id as contact_id,   "
          . "ride.comment as comment, contact.name as contact_name  "
          . " from ride , car, contact"
          ." where ride.car_id = :car_id and car.user_id = :user_id and ride.contact_id=contact.id ORDER BY ride.ride_date desc, ride.km DESC offset :offset limit :limit;" ;
        $values = array(":car_id" => $car_id, ":user_id" => $user_id, ":offset" => $offset,":limit" => $limit );
        try {
            $arr = $this->db->select($str, $values);
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function count_rides($user_id, $car_id)
    {
        $str =  "select count(*)  "
          . " from ride , car"
          ." where ride.car_id = :car_id and car.user_id = :user_id " ;
        $values = array(":car_id" => $car_id, ":user_id" => $user_id );
        try {
            $arr = $this->db->select($str, $values);
            return $arr[0];
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function list_lastride_contacts($user_id, $car_id)
    {
        try {
            $str = "select contact.id,contact.name,contact.street,contact.country,contact.zip,"
                . " contact.place from contact "
                . " where contact.is_deleted = FALSE and contact.user_id = :user_id";

            $values = array( ":user_id" => $user_id);
            $carr = $this->db->select($str, $values);
            $str = "select ride.id as id, ride.isBusiness as isBusiness, to_char( ride.ride_date , 'DD-MM-YY') as ride_date, "
                 . "ride.km as km, "
                 . "ride.contact_id as contact_id,  "
                 . "ride.comment as comment "
                 . " from ride, car"
                 . " where "
                 . " ride.car_id = :car_id and ride.car_id = car.id "
                 . " and car.user_id = :user_id"
                 . " order by km desc limit 1";
            $values = array(":car_id" => $car_id, ":user_id" => $user_id);
            $rarr = $this->db->select($str, $values);
            $arr = array("ride" => $rarr, "contacts" => $carr );
            return $arr;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}
