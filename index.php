<?php

require "connect.php";

$method = $_SERVER["REQUEST_METHOD"];

$accept = $_SERVER["HTTP_ACCEPT"];

$url = "https://stud.hosted.hr.nl/0892526/rest/players/";

switch($method)
{
    case "GET":
        $id = (isset($_GET['id']) ? $_GET['id'] : null);
        $start = isset($_GET['start']) ? $_GET['start'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : null;

        $sql = "SELECT * FROM players" .($id && !$limit ? " WHERE id =".$id : "").(!$id && $limit ? " LIMIT ".$limit." OFFSET ".($start -1) : "");
        $result = $conn->query($sql);

        /*
            If id is set, you're in the collection.
           Than show pagination and links
        */

        if ($id == null)
        {
            $rows = array();
            $rows["items"] = array();
            $rows["links"] = [
                [
                "rel" => "self",
                "href" => $url
                ]
            ];
            $rows["pagination"] = array();

            while ($r = mysqli_fetch_assoc($result))
            {
                $r["links"] = array(array("rel" => "self", "href" => $url . $r["id"]), array("rel" => "collection", "href" => $url));
                array_push($rows["items"], $r);
            }

            $query = "SELECT * FROM players";
            $resultTotal = $conn->query($query);
            $total = $resultTotal->num_rows;
            $limit = (!$limit ? $total : $limit);
            $limit = ($limit > $total ? $total : $limit);
            $start = (!$start ? 1 : $start);
            $num_rows = $result -> num_rows;


            $totalPages = ceil($total / $num_rows);
            $currentPage = ceil($start / $limit);
            $nextPageStart = ($start + $limit) > $total ? $total : ($start + $limit);
            $nextPage = ceil($start / $limit) + 1 > ceil($total / $num_rows) ? ceil($total / $num_rows) : ceil($start / $limit) + 1;
            $previousPageStart = $start - $limit < 1 ? 1 : $start - $limit;
            $previousPage = ceil($previousPageStart / $limit) < 1 ? 1 : ceil($previousPageStart / $limit);

            if ($currentPage < 1) $currentPage = 1;
            $rows["pagination"]["currentPage"] = $currentPage;
            $rows["pagination"]["currentItems"] = $num_rows;
            $rows["pagination"]["totalPages"] = $totalPages;
            $rows["pagination"]["totalItems"] = $total;
            $firstPagination = [
                "rel" => "first",
                "page" => 1,
                "href" => $url."players".($limit < $total ? "?start=1&limit=".$limit : "")
            ];
            $lastPagination = [
                "rel" => "last",
                "page" => $totalPages,
                "href" => $url."players".($limit < $total ? "?start=".($totalPages * $limit)."&limit=".$limit : "")
            ];
            $nextPagination = [
                "rel" => "next",
                "page" => $nextPage,
                "href" => $url."players".($limit < $total ? "start=".$nextPageStart."&limit=".$limit : "")
            ];
            $previousPagination = [
                "rel" => "previous",
                "page" => $previousPage,
                "href" => $url."players".($limit < $total ? "?start=".$previousPageStart."&limit=".$limit : "")
            ];
            $rows["pagination"]["links"] = [$firstPagination, $lastPagination,$previousPagination, $nextPagination];
        }

        else
        {
            $rows = mysqli_fetch_assoc($result);

            if (count($rows) == 0 )
            {
                http_response_code(404);
                exit;
            }

            $rows["links"] = array(array("rel" => "self", "href" => $url . $rows["id"]), array("rel" => "collection", "href" => $url));
        }

        if ($accept == "application/json")
        {
            header("Content-Type: application/json");
            http_response_code(200);

            print json_encode($rows);
        }

        else if ($accept == "application/xml")
        {
            header("Content-Type: application/xml");
            http_response_code(200);

            $xml = new SimpleXMLElement('<?xml version="1.0"?><players></players>');
            array_to_xml($rows, $xml);
            echo $xml -> asXML();
        }

        else
        {
            // content type niet toegestaan
            http_response_code(415);
        }

        $conn->close();
        break;

    case "POST":
        $content = $_SERVER["CONTENT_TYPE"];

        if ($content == "application/json")
        {
            $body = file_get_contents("php://input");
            $json = json_decode($body);

            if (isset($json->name) && (!empty($json->name) && isset($json->club) && !empty($json->club) && isset($json->age) && !empty($json->age) && isset($json->nationality) && !empty($json->nationality)))
            {
                $name = $json->name;
                $club = $json->club;
                $age = $json->age;
                $nationality = $json->nationality;

                $sql = "INSERT INTO players (name, age, club, nationality)
                    VALUES ('$name', '$age', '$club', '$nationality')";

                if ($conn->query($sql))
                {
                    http_response_code(201);
                }
            }


            else
            {
                http_response_code(406);
            }
        }

        else if ($content ==  "application/x-www-form-urlencoded")
        {
            $name = $_POST['name'];
            $club = $_POST['club'];
            $age = $_POST['age'];
            $nationality = $_POST['nationality'];

            if(!isset($name) || !isset($club) || !isset($age) || !isset($nationality))
            {
                http_response_code(406);
            }

            else
            {
                $sql = "INSERT INTO players (name, age, club, nationality)
                    VALUES ('$name', '$age', '$club', '$nationality')";

                if ($conn->query($sql))
                {
                    http_response_code(201);
                }
                else
                {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }
        }

        else
        {
            // content type niet toegestaan
            http_response_code(415);
        }

        $conn->close();
        break;

    case "DELETE":

        $id = (isset($_GET['id']) ? $_GET['id'] : null);

        if(!$id || empty($id))
        {
            // method niet toegestaan
            http_response_code(405);
        }

        else
        {
            $sql = "DELETE FROM players WHERE id='$id'";

            if ($conn->query($sql))
            {
                http_response_code(204);
            }
            else
            {
                echo "Error deleting record: " . $conn->error;
            }
        }

        $conn->close();
        break;

    case "PUT":

        $id = $_GET['id'];

        if ($id = (isset($_GET['id']) ? $_GET['id'] : null))
        {
             $body = file_get_contents("php://input");
             $json = json_decode($body);

             if (isset($json->name) && (!empty($json->name) && isset($json->club) && !empty($json->club) && isset($json->age) && !empty($json->age) && isset($json->nationality) && !empty($json->nationality))) {

                 $name = $json->name;
                 $club = $json->club;
                 $age = $json->age;
                 $nationality = $json->nationality;

                 $sql = "UPDATE players
                    SET name = '$name', age = '$age', club = '$club', nationality = '$nationality'
                    WHERE id = '$id'";

                 if ($conn->query($sql))
                 {
                     http_response_code(201);
                 }
             }

             else
             {
                 http_response_code(406);
             }
         }

         else
         {
             http_response_code(405);
         }

        break;

    case "OPTIONS":

        $id = (isset($_GET['id']) ? $_GET['id'] : null);

        if($id)
        {
            header("Allow: GET,PUT,DELETE,OPTIONS");
        }

        else
        {
            header("Allow: GET,POST,OPTIONS");
        }

        break;
}


/*
Function to output xml
*/

function array_to_xml($data, &$xml)
{
    foreach($data as $key => $value)
    {
        if (is_array($value))
        {
            if (!is_numeric($key))
            {
                $subnode = $xml->addChild("$key");
                array_to_xml($value, $subnode);
            }

            else
            {
                array_to_xml($value, $xml);
            }
        }

        else
        {
            $xml->addChild("$key","$value");
        }
    }
}
