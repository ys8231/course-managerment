<?php

require_once __DIR__ . '/vendor/autoload.php';
\Sentry\init(['dsn' => 'https://c5e70831bd5f4644a345eefb0daf7f03@o1110454.ingest.sentry.io/6139498' ]);
//throw new Exception("My first Sentry error!");
try {
    $this->functionFailsForSure();
} catch (\Throwable $exception) {
    \Sentry\captureException($exception);
}
//Create a PDO connection.
$db = new PDO('mysql:host=localhost;dbname=course', 'course', 'IofC2352686#');
$request = json_decode(file_get_contents('php://input'));
$type = $_GET['type'];
$result = null;
$db->exec("set names utf8");
//date_default_timezone_set('Asia/Taipei');
if ($type == 'create') {
    $createdTask = $request->models;
    $result = array();
    
    foreach($createdTask as $task) {        
                       
        //判斷學生欄位是否有資料及處理學生資料與年級學生資料的合併
        /**
        $studentArray = $task->Student;         
        If (empty($studentArray)) {
            $studentTotal = $studentArray;
        }
        Else {
            $studentField = implode(',', $studentArray); //將學生陣列資料轉換成字串           
            $studentTotal = $studentField;
        }
        */ 
        //將學生欄位的姓名字串轉換為陣列
        $Student_String = $task->Student;
        $Student_List= "";
        If ($Student_String) {
            $Student_Array = explode (",", $Student_String);            
            foreach ($Student_Array as $value) {
                $SubName = mb_substr($value,-2,2,'utf-8');
                //echo "$SubName";
                $Student_List = $Student_List . $SubName . ",";
            }
        }
        else {
            $Student_List = ""; 
        }

        //依照Title找出LessonID值，然後填入timetable資料表的Lesson欄位。
        $lessonName = $task->Title;
        $query = "SELECT * FROM lesson WHERE LessonName LIKE '$lessonName'";
        $statement = $db->prepare($query);
        $statement->execute(); 
        $lessonResult = $statement->fetchAll(PDO::FETCH_ASSOC);
        $lessonID =  $lessonResult[0]["LessonID"];
        // Create SQL INSERT statement
        $statement = $db->prepare('INSERT INTO timetable (Title, Lesson, Student, SubName, Grade, Start, End, StartTimezone, EndTimezone, RecurrenceRule, RecurrenceID, RecurrenceException, IsAllDay, OwnerID) 
        VALUES (:Title, :Lesson, :Student, :SubName, :Grade, :Start, :End, :StartTimezone, :EndTimezone, :RecurrenceRule, :RecurrenceID, :RecurrenceException, :IsAllDay, :OwnerID)');
        $startDate = $task->Start;
        $endDate = $task->End;
        $startDate = date('Y-m-d H:i:s',strtotime('+7 hour',strtotime($startDate)));
        $endDate = date('Y-m-d H:i:s',strtotime('+7 hour',strtotime($endDate)));
        // Bind parameter values
        //$statement->bindValue(':TaskID', $task->TaskID);
        $statement->bindValue(':Title', $task->Title);
        $statement->bindValue(':Lesson', $lessonID);        
        $statement->bindValue(':Student', $task->Student);
        $statement->bindValue(':SubName', $Student_List);
        $statement->bindValue(':Grade', $task->Grade);
        $statement->bindValue(':Start', $startDate);
        $statement->bindValue(':End', $endDate);
        $statement->bindValue(':StartTimezone', $task->StartTimezone);
        $statement->bindValue(':EndTimezone', $task->EndTimezone);
        $statement->bindValue(':RecurrenceRule', $task->RecurrenceRule);
        $statement->bindValue(':RecurrenceID', $task->RecurrenceID);
        $statement->bindValue(':RecurrenceException', $task->RecurrenceException);        
        $statement->bindValue(':IsAllDay', $task->IsAllDay); 
        $statement->bindValue(':OwnerID', $task->OwnerID);

        // Execute the statement
        $statement->execute();
        // Set ProductID to the last inserted ID (ProductID is auto-incremented column)
        $task->TaskID = $db->lastInsertId();

        // The result of the 'create' operation is all inserted products
        $result[] = $task;

    }
}

//Step 7 Implement read.
//下方語法是將數字轉換為 true or false 的方法
// IsAllDay As value, CASE WHEN IsAllDay = 1 THEN 'true' ELSE 'false' END AS AllDay
if ($type == 'read') {
    $query = "SELECT TaskID, Title, Lesson, Topic, Student, SubName, Start, End, StartTimezone, EndTimezone, 
    RecurrenceRule, RecurrenceID, RecurrenceException, OwnerID, Grade, Attendees, IsAllDay As value, 
    CASE WHEN IsAllDay = 1 THEN 'true' ELSE 'false' END AS IsAllDay FROM timetable";
    $statement = $db->prepare($query);
    $statement->execute(); 
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
}

//Step 8 更新資料 Implement update.

if ($type == 'update') {
    // in batch mode the updated records are available in the 'models' field
    $updatedProducts = $request->models;

    foreach($updatedProducts as $task) {
        
        //將學生欄位的姓名字串轉換為陣列
        $Student_String = $task->Student;
        $Student_str= "";
        If ($Student_String) {
            $Student_Array = explode (",", $Student_String);            
            foreach ($Student_Array as $value) {
                $SubName = mb_substr($value,-2,2,'utf-8');
                //echo "$SubName";
                $Student_str = $Student_str . $SubName . ",";
            }
        }
        else {
            $Student_str = ""; 
        }
        //移除學生名單最後的",＂符號
        $Student_List = substr_replace($Student_str, '', -1, 1);
        
        //依照Title找出LessonID值，然後填入timetable資料表的Lesson欄位。
        $lessonName = $task->Title;        
        $query = "SELECT * FROM lesson_all WHERE Title LIKE '$lessonName'";
        $statement = $db->prepare($query);
        $statement->execute(); 
        $lessonResult = $statement->fetchAll(PDO::FETCH_ASSOC);
        $lessonID =  $lessonResult[0]["Id"];

        // Create UPDATE SQL statement
        //$statement = $db->prepare('UPDATE timetable SET Title = :Title, Student = :Student, Start = :Start, End = :End, RecurrenceRule = :RecurrenceRule, 
        //RecurrenceID = :RecurrenceID, RecurrenceException = :RecurrenceException, IsAllDay = :IsAllDay , OwnerID = :OwnerID WHERE TaskID = :TaskID');
        $statement = $db->prepare('UPDATE timetable SET Title = :Title, Lesson = :Lesson, Student = :Student, SubName = :SubName,
        RecurrenceRule = :RecurrenceRule,RecurrenceID = :RecurrenceID, RecurrenceException = :RecurrenceException, 
        Start = :Start, End = :End, IsAllDay = :IsAllDay , OwnerID = :OwnerID , Grade = :Grade WHERE TaskID = :TaskID');
        $startDate = $task->Start;
        $endDate = $task->End;
        $startDate = date('Y-m-d H:i:s',strtotime('+7 hour',strtotime($startDate)));
        $endDate = date('Y-m-d H:i:s',strtotime('+7 hour',strtotime($endDate)));
        
        // Bind parameter values
        $statement->bindValue(':TaskID', $task->TaskID);
        $statement->bindValue(':Title', $task->Title);
        $statement->bindValue(':Lesson', $lessonID);
        $statement->bindValue(':Student', $task->Student);
        $statement->bindValue(':SubName', $Student_List);
        $statement->bindValue(':Grade', $task->Grade);
        $statement->bindValue(':Start', $startDate);       
        $statement->bindValue(':End', $endDate);
        //$statement->bindValue(':StartTimezone', $task->StartTimezone);
        //$statement->bindValue(':EndTimezone', $task->EndTimezone);       
        $statement->bindValue(':RecurrenceRule', $task->RecurrenceRule);
        $statement->bindValue(':RecurrenceID', $task->RecurrenceID);
        $statement->bindValue(':RecurrenceException', $task->RecurrenceException);        
        $statement->bindValue(':IsAllDay', $task->IsAllDay);
        $statement->bindValue(':OwnerID', $task->OwnerID);
        
        // Execute the statement
        $statement->execute();
    }
    
}

//Step 9 Implement destroy.

if ($type == 'destroy') {
    // in batch mode the destroyed records are available in the 'models' field
    $destroytask = $request->models;

    foreach($destroytask as $task) {
        // Create DELETE SQL statement
        $statement = $db->prepare('DELETE FROM timetable WHERE TaskID = :TaskID');

        // Bind parameter values
        $statement->bindValue(':TaskID', $task->TaskID);

        // Execute the statement
        $statement->execute();
    }
}
header('Content-Type: application/json');

//echo json_encode($result, JSON_UNESCAPED_UNICODE);
echo json_encode($result);
?>