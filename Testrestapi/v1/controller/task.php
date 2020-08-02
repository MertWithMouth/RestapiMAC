<?php


require_once('db.php');
require_once('../model/Task.php');
require_once('../model/Response.php');

try{

    $writeDB=DB::connectWriteDB();
    $readDB=DB::connectReadDB();


}

catch(PDOException $ex){

    error_log("Connection error - ".$ex, 0);
    $response=new Response();
    $response->setSuccess(false);
    $response->setHttpStatusCode(500);
    $response->addMessage("Database Connection Error");
    $response->send();
    exit();

}


if(array_key_exists("taskid", $_GET)){


    $taskid=$_GET['taskid'];
    if($taskid ==''|| !is_numeric($taskid)){


        $response= new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(400);
        $response->addMessage("Task ID cannot be blank or must be numeric ");
        $response->send();
        exit();
    }


    if($_SERVER['REQUEST_METHOD'] ==='GET'){

        try{

            $query=$readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id= :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();

            $rowCount=$query->rowCount();


            if($rowCount ===0){

                $response= new Response();
                $response->setSuccess(false);
                $response->setHttpStatusCode(404);
                $response->addMessage("Task not found!");
                $response->send();
                exit();

            }


            while($row=$query->fetch(PDO::FETCH_ASSOC)){

                $task =new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[]=$task->returnTaskAsArray();


            }

            $returnData=array();
            $returnData['rows_returned'] =$rowCount;
            $returnData['tasks']=$taskArray;



                $response= new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit();

        }
        catch(TaskException $ex){
            
            $response= new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
             $response->addMessage($ex->getMessage());
             $response->send();
                exit();

        }



        catch(PDOException $ex){

            error_log("Database query error - ".$ex, 0);
            $response=new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage("Failed to get Task");
            $response->send();
            exit();


        }


    }


    elseif($_SERVER['REQUEST_METHOD'] ==='DELETE'){

        try{

            $query = $writeDB -> prepare('delete from tbltasks where id =:taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();

            $rowCount= $query -> rowCount();

            if($rowCount ===0){

                $response= new Response();
                $response->setSuccess(false);
                $response->setHttpStatusCode(404);
                $response->addMessage("Task not found!");
                $response->send();
                exit();

            }


                $response= new Response();
                $response->setSuccess(true);
                $response->setHttpStatusCode(200);
                $response->addMessage("Task is deleted");
                $response->send();
                exit();


        }

        catch(PDOException $ex){
            $response= new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage("Failed to delete the task");
            $response->send();
            exit();




        }

    }

    elseif($_SERVER['REQUEST_METHOD'] ==='PATCH'){

    }

    //POST WON'T ASK ID cause it will be created
    else{


        $response= new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(405);
        $response->addMessage("Request method is not allowed");
        $response->send();
        exit();

    }

    

}

elseif(array_key_exists("completed", $_GET)){

    $completed= $_GET['completed'];

    if($completed !== 'Y' && $completed !== 'N'){

        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(400);
        $response->addMessage("Completed filter must be Y or N");
        $response->send();
        exit();

    }

    if($_SERVER['REQUEST_METHOD'] ==='GET'){

        try{

            $query=$readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where completed= :completed');
            $query->bindParam(':completed', $completed, PDO::PARAM_STR);
            $query->execute();

            $rowCount=$query->rowCount();

            $taskArray=array();
      

            while($row = $query ->fetch(PDO::FETCH_ASSOC)){

                $task =new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[]=$task->returnTaskAsArray();

            }

            $returnData=array();
            $returnData['rows_returned']=$rowCount;
            $returnData['tasks']=$taskArray;

                $response= new Response();
                $response->setSuccess(true);
                $response->setHttpStatusCode(200);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit();


        }

        catch(TaskException $ex){

        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(500);
        $response->addMessage($ex ->getMessage());
        $response->send();



        }

        catch(PDOException $ex){
            error_log("Database query error - ".$ex,0);
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage('Failed to get task');
            $response->send();
    

        }

    }

    else{
        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(405);
        $response->addMessage("Request method is not allowed");
        $response->send();

    }

        


}

elseif(array_key_exists("page", $_GET)){
    
    if($_SERVER['REQUEST_METHOD'] ==='GET'){

    $page= $_GET['page']; 

    if($page =='' || !is_numeric($page)){

        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(400);
        $response->addMessage("Page number cannot be blank and must be numeric");
        $response->send();
        exit();


        }

    $limitPerPage=20;

    try{

    $query = $readDB -> prepare('select count(id) as totalNoOfTasks from tbltasks');
    $query->execute();

    $row=$query->fetch(PDO::FETCH_ASSOC);
    $tasksCount = intval($row['totalNoOfTasks']);

    $numOfPages = ceil($tasksCount/$limitPerPage);

    if($numOfPages==0){
        $numOfPages=1;
    }

    if($page > $numOfPages || $page==0){

        $response = new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(404);
        $response->addMessage("Page is not found");
        $response->send();
        exit();

    }

    $offset = ($page ==1 ? 0 : ($limitPerPage*($page-1)));
    $query=$readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks limit :pglimit offset :offset');
    $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
    $query->bindParam(':offset', $offset, PDO::PARAM_INT);
    $query->execute();

    $rowCount=$query->rowCount();
    $taskArray=array();

    while($row=$query->fetch(PDO::FETCH_ASSOC)){

        $task =new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
        $taskArray[]=$task->returnTaskAsArray();

    }

    $returnData=array();
    $returnData['rows_returned']=$rowCount;
    $returnData['total_rows'] =$tasksCount;
    $returnData['total_pages']=$numOfPages;
    ($page< $numOfPages ? $returnData['has_next_page']=true : $returnData['has_next_page']=false);
    ($page< 1 ? $returnData['has_previous_page']=true : $returnData['has_previous_page']=false);
    $returnData['tasks']=$taskArray;

    $response= new Response();
    $response->setSuccess(true);
    $response->setHttpStatusCode(200);
    $response->toCache(true);
    $response->setData($returnData);
    $response->send();
    exit();

    }

    catch(TaskException $ex){
            
    $response= new Response();
    $response->setSuccess(false);
    $response->setHttpStatusCode(500);
    $response->addMessage($ex->getMessage());
    $response->send();
    exit();

    }



    catch(PDOException $ex){

        error_log("Database query error - ".$ex, 0);
        $response=new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(500);
        $response->addMessage($ex->getMessage());
        $response->send();
        exit();


    }


    }
    else{

        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method is not allowed");
        $response->send();


    }
}




elseif(empty($_GET)){

    if($_SERVER['REQUEST_METHOD'] ==='GET'){

        try{

            $query=$readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks');
            $query->execute();

            $rowCount=$query->rowCount();

            $taskArray=array();

            while($row = $query ->fetch(PDO::FETCH_ASSOC)){

                $task =new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[]=$task->returnTaskAsArray();

            }

            $returnData=array();
            $returnData['rows_returned']=$rowCount;
            $returnData['tasks']=$taskArray;

                $response= new Response();
                $response->setSuccess(true);
                $response->setHttpStatusCode(200);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit();

        }


        catch(TaskException $ex){

            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage($ex ->getMessage());
            $response->send();



        }

        catch(PDOException $ex){

            error_log("Database query error - ".$ex,0);
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage('Failed to get task');
            $response->send();



        }



    }

    elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // create task
    try {
      // check request's content type header is JSON
      if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        // set up response for unsuccessful request
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Content Type header not set to JSON");
        $response->send();
        exit;
      }
      
      // get POST request body as the POSTed data will be JSON format
      $rawPostData = file_get_contents('php://input');
      
      if(!$jsonData = json_decode($rawPostData)) {
        // set up response for unsuccessful request
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Request body is not valid JSON");
        $response->send();
        exit;
      }
      
      // check if post request contains title and completed data in body as these are mandatory
      if(!isset($jsonData->title) || !isset($jsonData->completed)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (!isset($jsonData->title) ? $response->addMessage("Title field is mandatory and must be provided") : false);
        (!isset($jsonData->completed) ? $response->addMessage("Completed field is mandatory and must be provided") : false);
        $response->send();
        exit;
      }
      
      // create new task with data, if non mandatory fields not provided then set to null
      $newTask = new Task(null, $jsonData->title, (isset($jsonData->description) ? $jsonData->description : null), (isset($jsonData->deadline) ? $jsonData->deadline : null), $jsonData->completed);
      // get title, description, deadline, completed and store them in variables
      $title = $newTask->getTitle();
      $description = $newTask->getDescription();
      $deadline = $newTask->getDeadline();
      $completed = $newTask->getCompleted();

      // create db query
      $query = $writeDB->prepare('insert into tbltasks (title, description, deadline, completed) values (:title, :description, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');
      $query->bindParam(':title', $title, PDO::PARAM_STR);
      $query->bindParam(':description', $description, PDO::PARAM_STR);
      $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
      $query->bindParam(':completed', $completed, PDO::PARAM_STR);
      $query->execute();
      
      // get row count
      $rowCount = $query->rowCount();

      // check if row was actually inserted, PDO exception should have caught it if not.
      if($rowCount === 0) {
        // set up response for unsuccessful return
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to create task");
        $response->send();
        exit;
      }
      
      // get last task id so we can return the Task in the json
      $lastTaskID = $writeDB->lastInsertId();
      // create db query to get newly created task - get from master db not read slave as replication may be too slow for successful read
      $query = $writeDB->prepare('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
      $query->bindParam(':taskid', $lastTaskID, PDO::PARAM_INT);
      $query->execute();

      // get row count
      $rowCount = $query->rowCount();
      
      // make sure that the new task was returned
      if($rowCount === 0) {
        // set up response for unsuccessful return
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to retrieve task after creation");
        $response->send();
        exit;
      }
      
      // create empty array to store tasks
      $taskArray = array();

      // for each row returned - should be just one
      while($row = $query->fetch(PDO::FETCH_ASSOC)) {
        // create new task object
        $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);

        // create task and store in array for return in json data
        $taskArray[] = $task->returnTaskAsArray();
      }
      // bundle tasks and rows returned into an array to return in the json data
      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['tasks'] = $taskArray;

      //set up response for successful return
      $response = new Response();
      $response->setHttpStatusCode(201);
      $response->setSuccess(true);
      $response->addMessage("Task created");
      $response->setData($returnData);
      $response->send();
      exit;      




        }

        catch(TaskException $ex){
            
            $response= new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        
            }
        
        
        
            catch(PDOException $ex){
        
                error_log("Database query error - ".$ex, 0);
                $response=new Response();
                $response->setSuccess(false);
                $response->setHttpStatusCode(500);
                $response->addMessage('Failed to insert task into database- check submitted data for errors');
                $response->send();
                exit();
        
        
            }
        





    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method is not allowed");
        $response->send();
    }







}

else{
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint not found");
    $response->send();
}



    



