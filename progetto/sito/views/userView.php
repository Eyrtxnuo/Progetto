<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'])) {
	header('Location: /login');
	exit;
}
if(!isset($userView)){
    exit("user not set?");
}
if($userView == $_SESSION['name']){
    exit("Sei tu ".$userView);
}


try {
    
    $conn = oci_pconnect(getenv("DB_USERNAME"), getenv("DB_PASSWORD"), getenv("DB_DATABASE"));
   /* $stid = oci_parse($conn, 'SELECT * FROM "Users" WHERE USERNAME = :user');

    oci_bind_by_name($stid, ":user", $userView);

    oci_execute($stid);*/
    $stid = oci_parse($conn,'SELECT NAME, SURNAME FROM "Users" WHERE Username = :usrn');
	
    oci_bind_by_name($stid, ':usrn', $userView);
    oci_execute($stid);
    if(!oci_fetch($stid)){
        exit($userView."-> "."User not found!".print_r(oci_error($stid),true));
    }
    


} catch(Exception $e) {
    exit('Failed to connect to Database. ' . $e->getMessage());
}

?>

Username: <?= $userView ?><br>
Name: <?= oci_result($stid, "NAME") ?><br>
Surname: <?= oci_result($stid, "SURNAME") ?><br>

<br>
<button onclick="window.location.href='/addDebt?user=<?= $userView ?>'">Aggiungi debito</button>
<br>

Totale debiti:  <?php
    $stid = oci_parse($conn,'SELECT GETUSERDEBT(:usrn, :other) FROM "DUAL"');
    oci_bind_by_name($stid, ':usrn', $_SESSION['name']);
    oci_bind_by_name($stid, ':other', $userView);
    oci_execute($stid);
    print_r(oci_fetch_array($stid)[0]);
?>

<br><br>

Storico debiti:
<ul>
<?php

$stid = oci_parse($conn,'
(SELECT -VALUE as VAL,ID,DESCRIPTION,CREATED_AT FROM "DEBTS" WHERE DEBTOR = :usrn AND CREDITOR = :other AND GROUP_ID IS NULL)
UNION  
(SELECT VALUE as VAL,ID,DESCRIPTION,CREATED_AT FROM "DEBTS"  WHERE DEBTOR = :other AND CREDITOR = :usrn AND GROUP_ID IS NULL)
ORDER BY CREATED_AT DESC
');
oci_bind_by_name($stid, ':usrn', $_SESSION['name']);
oci_bind_by_name($stid, ':other', $userView);
oci_execute($stid);
while($res = oci_fetch_array($stid)){
    echo("<li>".$res["VAL"])."€: ".$res["DESCRIPTION"]."</li>";
}
print_r(oci_error($stid));

?>
<ul>