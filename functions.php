<?php
/* Implement query_db_login - this function is used in login.php */
/* 
        Description - Must query the database to obtain the username that matches the 
        input parameters ($username, $password), or must return null if there is no match.
        The password is stored as MD5, so the query must convert the password received as parameter to
        MD5 and AFTER that interogate the DB with the MD5.
        PARAMETERS:
            $username: username field from post request
            $password: password field from post request
        MUST RETURN:
            null - if user credentials are not correct
            username - if credentials match a user
    */
function query_db_login($username, $password)
{
    $conn = get_mysqli();

    $found = null;

    if ($stmt = $conn->prepare('SELECT password FROM users WHERE username = ?')) {
        // Bind parameters s = string
        $stmt->bind_param('s', $username);
        $stmt->execute();
        // Store the result so we can check if the account exists in the database.
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($pass);
            $stmt->fetch();
            // Account exists, now we verify the password.
            if (md5($password) === $pass) {
                // Verification success! User has logged-in!
                $found = $username;
            }
        }
        $stmt->close();
    }
    $conn->close();
    return $found;
}

/* Implement get_message_rows - this function is used in index.php */
/* 
        Function must query the db and fetch all the entries from the 'messages' table
        (username, message - see MUST RETURN for more details) and return them in a separate array, 
        or return an empty array if there are no entries.
        PARAMETERS:
            No parameters
        MUST RETURN:
            array() - containing each of the rows returned by mysqli if there is at least one message
                      (code will use both $results['username'] and $results['message'] to display the data)
            empty array() - if there are NO messages
    */
function get_message_rows()
{
    $conn = get_mysqli();
    $results = array();

    if ($stmt = $conn->prepare('SELECT username, message FROM messages WHERE username = ?')) {
        $stmt->bind_param('s', $_SESSION["cookie"]);
        $stmt->execute();
        ($stmt_result = $stmt->get_result()) or trigger_error($stmt->error, E_USER_ERROR);
        if ($stmt_result->num_rows > 0) {
            while ($row_data = $stmt_result->fetch_assoc()) {
                array_push($results, $row_data);
            }
        }
        $stmt->close();
    }

    $conn->close();
    return $results;
}

/* Implement add_message_for_user - this function is used in index.php */
/* 
        Function must add the message received as parameter to the database's 'message' table.
        PARAMETERS:
            $username - username for the user submitting the message
            $message - message that the user wants to submit
        MUST RETURN:
            Return is irrelevant here
    */
function add_message_for_user($username, $message)
{
    $conn = get_mysqli();
    $results = array();


    if ($stmt = $conn->prepare('INSERT INTO messages (username, message) VALUES (?,?)')) {
        if (strlen($message) > 0) {
            $stmt->bind_param('ss', $username,  $message);
            $stmt->execute();
        }

        $stmt->close();
    }

    $conn->close();

    return $results;
}

/* Implement is_valid_image - this function is used in index.php */
/* 
        This function will validate if the file contained at $image_path is indeed an image.
        PARAMETERS:
            $image_path: path towards the file on disk
        MUST RETURN:
            true - file is an image
            false - file is not an image
    */
function is_valid_image($image_path)
{
    $size = getimagesize($image_path);
    $fp = fopen($image_path, "rb");
    if ($size && $fp) {
        return true;
    } else {
        return false;
    }
}

/* Implement add_photo_to_user - this function is used in index.php */
/* 
        This function must update the 'users' table and set the 'file_userphoto' field with 
        the value given to the $file_userphoto parameter
        PARAMETERS:
            $username - user for which to update the row
            $file_userphoto - value to be put in the 'file_userphoto' column (a path to an image)
        MUST RETURN:
            Return is irrelevant here
    */
function add_photo_path_to_user($username, $file_userphoto)
{
    $conn = get_mysqli();
    if ($stmt = $conn->prepare('UPDATE users SET file_userphoto = ?	 WHERE username = ?')) {
        $stmt->bind_param('ss', $file_userphoto,  $username);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

/* Implement get_photo_path_for_user - this function is used in index.php */
/* 
        This function must obtain from the 'users' table the field named file_userphoto and
        return is as a string. If there is nothing in the database, then return null.
        PARAMETERS:
            $username - user for which to query the file_userphoto column
        MUST RETURN:
            string - string containing the value from the DB, if there is such a value
            null - if there is no value in the DB
    */
function get_photo_path_for_user($username)
{
    $conn = get_mysqli();

    $path = null;

    if ($stmt = $conn->prepare('SELECT file_userphoto FROM users WHERE username = ?')) {
        // Bind parameters s = string
        $stmt->bind_param('s', $username);
        $stmt->execute();

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($file_userphoto);
            $stmt->fetch();
            $path = $file_userphoto;
        }

        $stmt->close();
    }
    $conn->close();

    return $path;
}

/* Implement get_memo_content_for_user - this function is used in index.php */
/* 
        This function must open the memo file for the current user from it's folder and return its content as a string.
        If the memo does not exist, the function must return the string "No such file!".
        PARAMETERS:
            $username - user for which obtain the memo file
            $memoname - the name of the memo the user requested to see
        MUST RETURN:
            string containing the data from the memo file (it's content)
            "No such file!" if there's no such file.
    */
function get_memo_content_for_user($username, $memoname)
{

    if (!preg_match('/^[\w.-]+$/', $memoname)) {
        return "No such file!";
    }

    $path = "users/" . $username . "/" . $memoname;
    if (!is_file($path)) {
        return "No such file!";
    }
    return file_get_contents($path);
}

/* 
        Evaluate the impact of 'get_language_php' by explaining what are the risks of this function's default implementation
        (the one you received) by answering the following questions:
            - What is the vulnerability present in this function?
            - What other vulnerability can be chained with this vulnerability to inflict damage on the web application and where is it present?
            - What can the attacker do once he chains the two vulnerabilities?
        After that, modify the get_language_php function to no longer present a security risk.
        This function is used in index.php
    */
/*
        This function must return the path to the language file corresponding to the desired language or null if the file
        does not exist. All language files must be in the language folder or else they are not supported.
        PARAMETERS:
            $language - desired language (e.g en)
        MUST RETURN:
            path to the en language file (languages/en.php)
            null if the language is not supported
    */
function get_language_php($language)
{

    if (!preg_match('/^[\w.-]+$/', $language)) {
        return null;
    }
    $language_path = "language/" . $language . ".php";
    if (is_file($language_path)) {
        return $language_path;
    }
    return null;
}
