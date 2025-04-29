<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once "$root/users/init.php";
if (!securePage($_SERVER['PHP_SELF'])) {
    die();
}
//require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
if (isset($user) && $user->isLoggedIn()) {
    require_once $abs_us_root . $us_url_root . 'usersc/includes/custom.php';
}




$personalsql = "SELECT * FROM ssh WHERE sshgroup = 'personal' AND createdby = '$userid' ORDER BY host ASC";
$personalquery = mysqli_query($local, $personalsql) or trigger_error(mysqli_error($local) . "[$personalsql]");

$gitsql = "SELECT * FROM ssh WHERE sshgroup = 'git' ORDER BY host ASC";
$gitquery = mysqli_query($local, $gitsql) or trigger_error(mysqli_error($local) . "[$gitsql]");

$internalsql = "SELECT * FROM ssh WHERE sshgroup = 'internal' ORDER BY host ASC";
$internalquery = mysqli_query($local, $internalsql) or trigger_error(mysqli_error($local) . "[$internalsql]");

$awsworksql = "SELECT * FROM ssh WHERE sshgroup = 'aws-work' ORDER BY host ASC";
$awsworkquery = mysqli_query($local, $awsworksql) or trigger_error(mysqli_error($local) . "[$awsworksql]");

$worksql = "SELECT * FROM ssh WHERE sshgroup = 'mikework' ORDER BY host ASC";
$workquery = mysqli_query($local, $worksql) or trigger_error(mysqli_error($local) . "[$worksql]");

$websql = "SELECT * FROM ssh WHERE sshgroup = 'web' ORDER BY host ASC";
$webquery = mysqli_query($local, $websql) or trigger_error(mysqli_error($local) . "[$websql]");

$webdevsql = "SELECT * FROM ssh WHERE sshgroup = 'webdev' ORDER BY host ASC";
$webdevquery = mysqli_query($local, $webdevsql) or trigger_error(mysqli_error($local) . "[$webdevsql]");

$externalsql = "SELECT * FROM ssh WHERE sshgroup = 'external' ORDER BY host ASC";
$externalquery = mysqli_query($local, $externalsql) or trigger_error(mysqli_error($local) . "[$externalsql]");

$hasTables = true;


echo "###General settings###<br>";
echo "Host *<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/id_rsa<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;TCPKeepAlive yes<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;ForwardAgent yes<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;ServerAliveInterval 10<br>";
            
echo "<br>### Userful VPN### <br>";
echo "Host 172.26.0.*<br>"
            
            . "&nbsp;&nbsp;&nbsp;&nbsp;ForwardX11 yes<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;GSSAPIAuthentication=no<br>";

echo "<br>### Personal / Custom ###<br><br>";
while ($personal = mysqli_fetch_assoc($personalquery)) {
            $host = $personal['host'];
            $hostname = $personal['hostname'];
            $port = $personal['port'];
            $sshuser = $personal['user'];
            $identityfile = $personal['identityfile'];
            $proxyun = $personal['proxyun'];
            $proxyjump = $personal['ProxyJump'];
            $forwardlocal = $personal['forwardlocal'];
            $forwardremote = $personal['forwardremote'];
            
            echo "Host $host<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
            if (!empty($proxyjump)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyun@$proxyjump<br>";
            }
            if (!empty($identityfile)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
                . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
            }
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
            ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
          if (!empty($forwardlocal)) {      
              echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
          }
          echo "<br><br>";
}

if (hasPerm([2,6])) {
    echo "<br>### Git ###<br><br>";
    while ($git = mysqli_fetch_assoc($gitquery)) {
        $host = $git['host'];
        $hostname = $git['hostname'];
        $port = $git['port'];
        $proxyjump = $git['ProxyJump'];
        $sshuser = $git['user'];
        $identityfile = $git['identityfile'];
        $forwardlocal = $git['forwardlocal'];
        $forwardremote = $git['forwardremote'];
                
        echo "Host $host<br>"
        . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
        if (!empty($proxyjump)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyjump<br>";
        }
        if (!empty($identityfile)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
        }
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
        ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
      if (!empty($forwardlocal)) {      
          echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
      }
      
              echo "<br><br>";
    }
    }

if (hasPerm([2,6])) {
    echo "<br>### Web Servers ###<br><br>";
    while ($web = mysqli_fetch_assoc($webquery)) {
        $host = $web['host'];
        $hostname = $web['hostname'];
        $port = $web['port'];
        $proxyjump = $web['ProxyJump'];
        $sshuser = $web['user'];
        $identityfile = $web['identityfile'];
        $forwardlocal = $web['forwardlocal'];
        $forwardremote = $web['forwardremote'];
                
        echo "Host $host<br>"
        . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
        if (!empty($proxyjump)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyjump<br>";
        }
        if (!empty($identityfile)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
        }
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
        ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
      if (!empty($forwardlocal)) {      
          echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
      }
      
              echo "<br><br>";
    }
    }

    if (hasPerm([2,6])) {
        echo "<br>### Web Dev Servers ###<br><br>";
        while ($webdev = mysqli_fetch_assoc($webdevquery)) {
            $host = $webdev['host'];
            $hostname = $webdev['hostname'];
            $port = $webdev['port'];
            $proxyjump = $webdev['ProxyJump'];
            $sshuser = $webdev['user'];
            $identityfile = $webdev['identityfile'];
            $forwardlocal = $webdev['forwardlocal'];
            $forwardremote = $webdev['forwardremote'];
                    
            echo "Host $host<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
            if (!empty($proxyjump)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyjump<br>";
            }
            if (!empty($identityfile)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
                . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
            }
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
            ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
          if (!empty($forwardlocal)) {      
              echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
          }
          
                  echo "<br><br>";
        }
        }

if (hasPerm([2,6])) {
    echo "<br>### Internal Network ###<br><br>";
    while ($internal = mysqli_fetch_assoc($internalquery)) {
        $host = $internal['host'];
        $hostname = $internal['hostname'];
        $port = $internal['port'];
        $proxyjump = $internal['ProxyJump'];
        $sshuser = $internal['user'];
        $identityfile = $internal['identityfile'];
        $forwardlocal = $internal['forwardlocal'];
        $forwardremote = $internal['forwardremote'];
                
        echo "Host $host<br>"
        . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
        if (!empty($proxyjump)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyjump<br>";
        }
        if (!empty($identityfile)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
        }
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
        ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
      if (!empty($forwardlocal)) {      
          echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
      }
      
              echo "<br><br>";
    }
    }

    if (hasPerm([2,6])) {
    echo "<br>### Userful AWS ###<br><br>";
    while ($awswork = mysqli_fetch_assoc($awsworkquery)) {
        $host = $awswork['host'];
        $hostname = $awswork['hostname'];
        $port = $awswork['port'];
        $proxyun = $awswork['proxyun'];
        $proxyjump = $awswork['ProxyJump'];
        $sshuser = $awswork['user'];
        $identityfile = $awswork['identityfile'];
        $forwardlocal = $awswork['forwardlocal'];
        $forwardremote = $awswork['forwardremote'];
                
        echo "Host $host<br>"
        . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
        if (!empty($proxyjump)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyun@$proxyjump<br>";
        }
        if (!empty($identityfile)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
        }
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
        ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
      if (!empty($forwardlocal)) {      
          echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
      }
      
              echo "<br><br>";
         
    }
    }
    

if (hasPerm([2,5,6])) {
    echo "<br>### Mikes Work stuff ###<br><br>";
    while ($work = mysqli_fetch_assoc($workquery)) {
        $host = $work['host'];
        $hostname = $work['hostname'];
        $port = $work['port'];
        $proxyjump = $work['ProxyJump'];
        $sshuser = $work['user'];
        $identityfile = $work['identityfile'];
        $forwardlocal = $work['forwardlocal'];
        $forwardremote = $work['forwardremote'];
                
        echo "Host $host<br>"
        . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
        if (!empty($proxyjump)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyjump<br>";
        }
        if (!empty($identityfile)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
        }
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
        ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
      if (!empty($forwardlocal)) {      
          echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
      }
      
              echo "<br><br>";
    }
    }

    if (hasPerm([2,6])) {
        echo "<br>### External ###<br><br>";
        while ($external = mysqli_fetch_assoc($externalquery)) {
            $host = $external['host'];
            $hostname = $external['hostname'];
            $port = $external['port'];
            $proxyjump = $external['ProxyJump'];
            $sshuser = $external['user'];
            $identityfile = $external['identityfile'];
            $forwardlocal = $external['forwardlocal'];
            $forwardremote = $external['forwardremote'];
                    
            echo "Host $host<br>"
            . "&nbsp;&nbsp;&nbsp;&nbsp;Hostname $hostname<br>";
            if (!empty($proxyjump)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;ProxyJump $proxyjump<br>";
            }
            if (!empty($identityfile)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;IdentityFile ~/.ssh/$identityfile<br>"
                . "&nbsp;&nbsp;&nbsp;&nbsp;IdentitiesOnly yes<br>";
            }
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Port $port<br>"
            ."&nbsp;&nbsp;&nbsp;&nbsp;User $sshuser";
          if (!empty($forwardlocal)) {      
              echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;LocalForward $forwardlocal localhost:$forwardremote";
          }
          
                  echo "<br><br>";
        }
        }   
?>

                </p>
            </div>
        </div>
        <?php //require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; 
        ?>