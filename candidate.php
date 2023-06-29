<?php
/*
  * Plugin Name: Export Candidates To CSV
  * Plugin URI: https://github.com/lucsfernandes
  * Description: Um plugin para exportar dados de formulário ACF para CSV.
  * Version: 1.0.0
  * Author: Lucas Fernandes
  * Author URI: https://github.com/lucsfernandes
*/

if(!@\session_start())
    session_start();

require_once(__DIR__ . '/controllers/candidates.php');

new RHCandidatesController();
