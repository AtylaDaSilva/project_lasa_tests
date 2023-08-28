<?php
require_once "ws.php";
class z_collect_form_get_email extends ws {

    //Takes a string of text from a pdf document and returns the data depending on what is passed to $field. matchFormData uses regular expressions to find the data in the string of text
    function matchFormData($text, $field)
    {
        if (empty($text) || empty($field)) return false;

        switch (strtolower($field)) {
                //Retorna a referência da guia no formato de banco de dados (aaaa-mm-dd)
            case "reference":
                $res = "";
                $regExp = "/(([a-z]{3})|\d{2})\/20\d{2}/im";
                $regExpAlt = "/^(([a-z]{3})|\d{2})\/20\d{2}$/i";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    //GNRE Múltipla
                    if (strpos(strtoupper(str_replace(" ", "", $text)), "ITENSDAGNRE") !== false) {
                        $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));

                        foreach ($arr as $str) {
                            //Matches dd/mm/20yy
                            if (preg_match("/\d{1,2}\/\d{1,2}\/20\d{2}/m", $str, $matches)) {
                                $dueDate = explode("/", $matches[0]);

                                $res = $dueDate[2] . "-" . $dueDate[1] . "-01";
                            }
                        }
                    } else { //GNRE Tradicional
                        $arr = explode("\t", $text);
                        $ref = "";
                        foreach ($arr as $string) {
                            //Matches Jul/20yy or 08/20yy
                            if (preg_match_all($regExpAlt, $string, $matches)) {
                                $r = explode("/", $matches[0][0]);

                                $res = $r[1] . "-" . $r[0] . "-01";
                            }
                        }

                        //If Previous mehtod did not work
                        if (empty($res)) {
                            //Some GNREs lack reference date, so it's necessary to get it from due date
                            $due_date = $this->matchFormData($text, "due_date");
                            $ref = explode("-", $due_date);
                            $res = $ref[0] . "-" . $ref[1] . "-01";
                        }
                    }
                }

                //Guias de Estado
                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    if (sizeof($arr = explode("REF", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExp, $arr[1], $matches)) {
                            $r = explode("/", $matches[0]);

                            $res = $r[1] . "-" . $r[0] . "-01";
                        }
                    }
                }

                //Acre
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOACRE") !== false) {
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExpAlt, $str, $matches)) {
                            $res = $matches[0];
                            //Checks if date format is Jul/2022
                            $date = explode("/", $res);

                            if (strlen($date[0]) > 2) { //Format is Jul/2022
                                $month = array(
                                    "JAN" => "01",
                                    "FEV" => "02",
                                    "MAR" => "03",
                                    "ABR" => "04",
                                    "MAI" => "05",
                                    "JUN" => "06",
                                    "JUL" => "07",
                                    "AGO" => "08",
                                    "NOV" => "09",
                                    "OUT" => "10",
                                    "NOV" => "11",
                                    "DEZ" => "12"
                                );

                                $res = $date[1] . "-" . $month[strtoupper($date[0])] . "-01";
                            }
                        }
                    }

                    //If $res was not captured, try another method
                    if (empty($res)) {
                        $arr = explode("DATADALAVRATURA", str_replace([" ", "\n", "\t"], "", strtoupper($text)));
                        $substr = substr($arr[1], 0, 50);
                        
                        //Esse layout de guias do AC vem com REFERENCIA no formato dd/mm/aaaa
                        if (preg_match("/\d{2}\/\d{2}\/\d{4}/", $substr, $matches)) {
                            $ref = explode("/", $matches[0]);
                            $res = "$ref[2]-$ref[1]-01";
                        }
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("REFER", strtoupper($text));

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $arr[1], $matches)) {
                        $r = explode("/", $matches[0]);

                        $res = $r[1] . "-" . $r[0] . "-01";
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("REFER", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $arr = explode("REFER", strtoupper($text));

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $arr[1], $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("DAR/CB", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 125);
                        
                        if (preg_match_all("/\d{2}\/20\d{2}/", $sub_str, $matches)) {
                            $ref = explode("/", $matches[0][1]);
                            $res = "$ref[1]-$ref[0]-01";
                        }
                    } else {
                        $arr = explode("\t", strtoupper($text));

                        foreach ($arr as $str) {
                            //Matches Jul/20yy or 08/20yy
                            if (preg_match($regExpAlt, $str, $matches)) {
                                $date = explode("/", $matches[0]);
                                $res = "$date[1]-$date[0]-01";
                            }
                        }
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 175);
                    $sub_arr = explode("\t\n", $sub_str);

                    foreach ($sub_arr as $str) {
                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExpAlt, $str, $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                            break;
                        }
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("REFER", strtoupper(str_replace(" ", "", $text)));

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $arr[1], $matches)) {
                        $r = explode("/", $matches[0]);

                        $res = $r[1] . "-" . $r[0] . "-01";
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("\t\n", $text);

                    foreach ($arr as $str) {
                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExpAlt, $str, $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 150);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("\t\n", str_replace(" ", "", $text));

                    foreach ($arr as $str) {
                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExpAlt, $str, $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                        }
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") == false
                ) {
                    $arr = explode("REF.", str_replace(" ", "", $text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Mato Grosso do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $arr = explode("REFER", str_replace(" ", "", $text));
                    $sub_str = substr($arr[2], 6, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)));
                        $sub_str = substr($arr[1], 0, 60);

                        if (preg_match($regExp, $arr[1], $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                        }
                    } else if (sizeof($arr = explode("RECEBERAT", str_replace(" ", "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                        }
                    }
                    
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("DATADEVENCIMENTO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $date = explode("/", $matches[0][0]);
                            $res = "$date[1]-$date[0]-01";
                        }
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("REFER", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 60);

                    if (preg_match("/\d{9}/", $sub_str, $matches)) {
                        $ref = str_split(substr($matches[0], -6), 2);
                        $res = $ref[1] . $ref[2] . "-" . $ref[0] . "-01";
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("DATAPROCESS.", str_replace(" ", "", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $ref = explode("/", $matches[0]);
                        $res = "$ref[1]-$ref[0]-01";
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas do relatorio
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                    ) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("CNPJOUCPF", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("TOTALAPAGAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $date = explode("/", $matches[0]);
                        $res = "$date[1]-$date[0]-01";
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExp, $str, $matches)) {
                            $date = explode("/", $matches[0]);
                            $res = "$date[1]-$date[0]-01";
                            break;
                        }
                    }
                }

                return $res;

                //Retorna a data de vencimento da guia no formato de banco de dados (aaaa-mm-dd)
            case "due_date":
                $regExp = "/\d{1,2}\/\d{1,2}\/20\d{2}/m";
                $substring = substr(strtolower($text), strpos(strtolower($text), "venci"));

                //Matches dd/mm/20yy
                $res = preg_match_all($regExp, $substring, $matches) != false ? $matches[0][0] : false;

                //GNRE Múltipla
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ITENSDAGNRE") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));

                    foreach ($arr as $str) {
                        //Matches dd/mm/20yy
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOACRE") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $substr = substr($arr[1], 0, 150);

                    if (preg_match_all($regExp, $substr, $matches)) {
                        $res = $matches[0][1];
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    if (sizeof($arr = explode("VENCIMENTO", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 25);

                        if (preg_match($regExp, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Alagoas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 25);

                        if (preg_match($regExp, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 275);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 175);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "PARÁ") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "GOVERNODOESTADO") !== false
                ) {
                    
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 60);

                        if (preg_match($regExp, $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("DATAEMISS", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("DATADEVENCIMENTO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][0];
                        }
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                return strToDate($res);

                //Retorna o valor da guia
            case "value":
                $res = "0.00";
                $regExp = "/(\d{1,}\.)?(\d{1,}\.)?\d{1,},\d{2}/"; //Dá match em valores similares a 00,00
                $regExpAlt = "/^(\d{1,}.)?(\d{1,}.)?\d{1,},\d{2}$/";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    if (preg_match($regExp, $text, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Acre
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOACRE") !== false) {
                    if (sizeof($arr = explode("DEMONSTRATIVO", str_replace(" ", "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 100);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][1];
                        }
                    } else if (sizeof($arr = explode("TRIBUTO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        if (preg_match($regExp, $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Guias de Estado
                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("TRIBUTO", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 50);
                        
                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = explode("PRINCIPAL", strtoupper($text));

                        if (preg_match($regExp, $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Mato Grosso do Sul ou Piauí
                if (
                    strpos(strtoupper($text), "MATO GROSSO DO SUL") ||
                    strpos(strtoupper($text), "ESTADO DO PIAU")
                ) {
                    $arr = explode("PRINCIPAL", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (strpos(strtoupper($text), "ESTADO DO AMAP")) {
                    $arr = explode("PARCELA", strtoupper($text));

                    foreach ($arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("VALORPRINCIPAL", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("RECEITA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[3], 0, 20);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 150);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $arr = explode("TOTAL A RECOLHER", strtoupper($text));

                    foreach ($arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("NOMEDOCONTRIBUINTE", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][5];
                        }
                    } else if (sizeof($arr = explode("MUNIC.:", strtoupper(str_replace([" ", "\n", "\t"], " ", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);
                        $sub_arr = explode(" ", $sub_str);

                        $match = array();

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, $str, $matches)) {
                                array_push($match, $matches[0]);
                            }
                        }

                        $res = $match[1];
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("10-VALORPRINCIPAL", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("TOTAL", strtoupper($text));
                    $sub_str = substr($arr[0], -50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("VALORDODOCUMENTO", str_replace(" ", "", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("VALOR:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Tocantins
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOTOCANTINS") !== false) {
                    if (sizeof($arr = explode("ORIGEMDOCONTRIBUINTE", str_replace([" ", "\N", "\T"], ["", "\n", "\n"], strtoupper($text)))) > 1) {
                        $substr = substr($arr[3], 0, 250);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][4];
                        }

                        //Valor veio no formato "valor1 valor1". Quebrar no " " e pegar o primeiro valor1
                        if (strpos($res, " ") !== false) {
                            $res = explode(" ", $res)[0];
                        }
                    } else if (
                        sizeof($arr = explode("CONTRIBUINTEOUTRAUFPOROPERA", str_replace([" "], "", strtoupper($text)))) > 1 ||
                        sizeof($arr = explode("CONTRIBUINTEOUTRAUFPORAPURA", str_replace([" "], "", strtoupper($text)))) > 1
                    ) {
                        $substr = substr($arr[1], 0, 125);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][5];
                        }
                    } else if (sizeof($arr = explode("ICMSDIFERENCIALDEAL", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 125);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][4];
                        }
                    }
                }

                //Retorna valores com "." em vez de ","
                return preg_replace("/^(\d{1,})?.?(\d{1,}),(\d{2})$/", "$1$2.$3", $res, 1);

                //Retorna o valor da multa da guia
            case "fine":
                $res = "0.00";
                $regExp = "/(\d{1,}\.)?\d{1,},\d{2}/"; //Dá match em valores similares a 00,00
                $regExpAlt = "/^(\d{1,}\.)?\d{1,},\d{2}$/";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $arr = explode("RESERVADO", strtoupper($text));

                    if (preg_match($regExp, $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Guias de Estado
                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("MULTA", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Acre
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODOACRE")) {
                    if (sizeof($arr = explode("MULTAMORAT", str_replace(" ", "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 100);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][1];
                        }
                    } else {
                        $arr = explode("GOVERNO DO ESTADO DO ACRE", strtoupper($text));

                        if (preg_match_all($regExp, $arr[1], $matches)) {
                            $res = $matches[0][1];
                        }
                    }
                    
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);
                        
                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][4];
                        }
                    } else {
                        $arr = explode("MULTA", strtoupper($text));
                        $sub_arr = explode("\t", $arr[1]);

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, str_replace(" ", "", $str), $matches)) {
                                $res = $matches[0];
                                break;
                            }
                        }
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("MULTA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará, Piauí, Mato Grosso ou Mato Grosso do Sul
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false ||
                    strpos(strtoupper($text), "ESTADO DO PIAU") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false
                ) {
                    $arr = explode("MULTA", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (strpos(strtoupper($text), "GOVERNO DO ESTADO DO AMAP")) {
                    $arr = explode("JUROS", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 85);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][1];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 200);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][2];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("MULTA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 20);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][3];
                        }
                    } else if (sizeof($arr = explode("MUNIC.:", strtoupper(str_replace([" ", "\n", "\t"], " ", $text)))) > 1) {
                        $sub_arr = explode(" ", $arr[1]);

                        $match = array();

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, $str, $matches)) {
                                array_push($match, $matches[0]);
                            }
                        }

                        $res = $match[4];
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 100);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][1];
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("MODALIDADEGNRE", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[3], 0, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][1];
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $arr = explode("ICMS", str_replace(["\N", "\T", " "], " ", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 150);
                    $sub_arr = explode(" ", $sub_str);

                    $match = array();

                    foreach ($sub_arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            array_push($match, $matches[0]);
                        }
                    }

                    $res = $match[0];
                }

                //Retorna valores com "." em vez de ","
                return preg_replace("/^(\d{1,})?.?(\d{1,}),(\d{2})$/", "$1$2.$3", $res, 1);

                //Retorna o valor dos juros da guia
            case "interest":
                $res = "0.00";
                $regExp = "/(\d{1,}\.)?\d{1,},\d{2}/"; //Dá match em valores similares a 00,00

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    //Exploded at "digo da Receita" to avoid possible problems with special characters
                    $arr = explode("DIGO DA RECEITA", strtoupper($text));

                    if (preg_match($regExp, $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Guias de Estado
                //Amazonas, Mato Grosso e Mato Grosso do Sul
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false
                ) {
                    $arr = explode("JUROS", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Acre
                if (strpos(strtoupper($text), "GOVERNO DO ESTADO DO ACRE")) {
                    $arr = explode("GOVERNO DO ESTADO DO ACRE", strtoupper($text));
                    $substring = substr($arr[1], 0, 19);

                    if (preg_match($regExp, $substring, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("MONET", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("JUROS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará ou Alagoas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false || strpos(strtoupper($text), "ESTADO DE ALAGOAS")) {
                    $arr = explode("JUROS", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 85);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][2];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("JUROS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 20);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 200);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][3];
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("REFER", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 75);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][2];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    $arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 150);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][2];
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 100);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][2];
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $arr = explode("ICMS", str_replace("\N", "\n", strtoupper($text)));
                    $sub_arr = explode("\n", $arr[1]);
                    $count = 1;

                    //Capturar o terceiro match
                    foreach ($sub_arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            if ($count == 3) {
                                $res = $matches[0];
                            }
                            $count++;
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("JUROS", strtoupper($text));

                    if (preg_match_all($regExp, $arr[1], $matches)) {
                        $res = $matches[0][1];
                    }
                }

                //Retorna valores com "." em vez de ","
                return preg_replace("/^(\d{1,})?.?(\d{1,}),(\d{2})$/", "$1$2.$3", $res, 1);

                //Retorna o valor total da guia
            case "total":
                $res = "0.00";
                $regExp = "/(\d{1,}\.)?(\d{1,}\.)?\d{1,},\d{2}/"; //Dá match em valores similares a 00,00
                $regExpAlt = "/^(\d{1,}\.)?(\d{1,}\.)?\d{1,},\d{2}$/";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $arr = explode("TOTAL A RECOLHER", strtoupper($text));

                    if (preg_match($regExp, $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Guias de Estado
                //Acre
                if (strpos(str_replace(["\n", "\t", " "], "", strtoupper($text)), "ESTADODOACRE") !== false) {
                    if (sizeof($arr = explode("TOTAIS TRANSPORTADOS", strtoupper($text))) > 1) {
                        $substr = substr($arr[1], 0, 50);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][1];
                        }

                    } else {
                        $arr = explode("\n", str_replace("\N", "\n", strtoupper($text)));

                        foreach ($arr as $str) {
                            if (preg_match($regExpAlt, trim($str), $matches)) {
                                $res = $matches[0];
                            }
                        }

                        //Valor veio no formato "valor1 valor1". Quebrar no " " e pegar o segundo valor1
                        if (strpos($res, " ") !== false) {
                            $res = explode(" ", $res)[1];
                        }
                    }
                }

                //Tocantins
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOTOCANTINS")) {
                    if (sizeof($arr = explode("ICMS-COMPLEMENTAR", str_replace([" ", "\N", "\T"], ["", "\n", "\n"], strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 100);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][4];
                        }

                        //Valor veio no formato "valor1 valor1". Quebrar no " " e pegar o primeiro valor1
                        if (strpos($res, " ") !== false) {
                            $res = explode(" ", $res)[0];
                        }
                    } else if (
                        sizeof($arr = explode("CONTRIBUINTEOUTRAUFPOROPERA", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1 ||
                        sizeof($arr = explode("CONTRIBUINTEOUTRAUFPORAPURA", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1
                    ) {
                        $substr = substr($arr[3], 0, 25);

                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][1];
                        }
                    } else if (sizeof($arr = explode("ICMSDIFERENCIALDEAL", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 125);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][4];
                        }
                    } else {
                        $arr = explode("\n", str_replace("\N", "\n", strtoupper($text)));

                        foreach ($arr as $str) {
                            if (preg_match($regExpAlt, trim($str), $matches)) {
                                $res = $matches[0];
                            }
                        }

                        //Valor veio no formato "valor1 valor1". Quebrar no " " e pegar o segundo valor1
                        if (strpos($res, " ") !== false) {
                            $res = explode(" ", $res)[1];
                        }
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    if (sizeof($arr = explode("18VALOR", strtoupper(str_replace([" ", "\n", "\t"], "", $text)))) > 1) {
                        $sub_str = substr($arr[2], 0, 25);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = explode("TOTAL", strtoupper(str_replace([" ", "\n", "\t"], "", $text)));

                        if (preg_match($regExp, $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);
                        
                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][5];
                        }
                    } else {
                        $arr = explode("TOTAL", strtoupper(str_replace(" ", "", $text)));
                        if (preg_match($regExp, $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Mato Grosso do Sul e Ceará
                if (
                    (strpos(strtoupper($text), "ESTADO DE ALAGOAS")) ||
                    (strpos(strtoupper($text), "MATO GROSSO DO SUL")) ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false
                ) {
                    //$substring = substr($text, strpos(strtoupper($text), "TOTAL"));
                    $arr = explode("TOTAL", strtoupper(str_replace(" ", "", $text)));
                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (
                    strpos(strtoupper($text), "ESTADO DO AMAP")
                ) {
                    $substring = substr($text, strpos(strtoupper($text), "MULTA"));
                    if (preg_match($regExp, $substring, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("TOTALARECOLHER", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 85);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][4];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("TOTAL", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 20);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 200);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][1];
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $substring = substr($text, strpos(strtoupper($text), "VALOR"));
                    if (preg_match($regExp, $substring, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][6];
                        }
                    } else if (sizeof($arr = explode("MUNIC.:", strtoupper(str_replace([" ", "\n", "\t"], " ", $text)))) > 1) {
                        $sub_arr = explode(" ", $arr[1]);

                        $match = array();

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, $str, $matches)) {
                                array_push($match, $matches[0]);
                            }
                        }

                        $res = $match[6];
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("15-TOTALARECOLHER", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("TOTAL A RECOLHER", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("VALORTOTAL", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 75);

                    if (preg_match_all($regExp, $sub_str, $matches)) {
                        $res = $matches[0][3];
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("TOTAL", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("VALORDODOCUMENTO", str_replace(" ", "", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("TOTAL:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Retorna valores com "." em vez de ","
                return preg_replace("/^(\d{1,})?.?(\d{1,}),(\d{2})$/", "$1$2.$3", $res, 1);

                //Retorna "GNRE" se a guia passada em $text for GNRE ou "State" se for guia de estado.
            case "type":
                $res = "";
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $res = "GNRE";
                } else {
                    $res = "State";
                }

                return $res;

                //Retorna a ID em collect_form_type_id do respectivo Estado da guia
            case "collect_form_type_id":
                $res = "";
                $state = "";
                $query = "SELECT cft.id FROM files_one.collect_form_type cft WHERE cft.state = '$state' AND cft._status = 'active'";

                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $res = 13; //GNRE SP
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $state = "CE";
                }

                if (strpos(strtoupper($text), "ESTADO DE ALAGOAS")) {
                    $state = "AL";
                }

                $res = ws::qfield($query);

                return $res;

                //Retorna o codigo do municipio da guia
            case "city_code":
                $res = "";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $res = preg_match_all("/\d{5}-\d{3}/m", $text, $matches) != false ? $matches : false;
                    $arr = explode("\t", $text);

                    foreach ($arr as $str) {
                        if (preg_match("/\d{5}-\d{3}/", trim($str), $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Guias de Estado
                //Acre
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOACRE") !== false) {
                    if (sizeof($arr = explode("SUJEITOPASSIVO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $substr = substr($arr[1], 0, 50);

                        if (preg_match("/\d{6}\-\d{1}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    if (sizeof($arr = explode("D.MUNIC", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $substr = substr($arr[1], 0, 20);

                        if (preg_match("/\d{3,5}/", $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("AUTENTICA", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{5}-\d{3}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("DIGODOMUNIC", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match("/\d{5}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false && strpos(strtoupper($text), "CEP")) {
                    $arr = explode("CEP", $text);

                    if (preg_match("/(\d{5}-\d{3})|(\d{8})/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("CEP", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match("/\d{2}\.?\d{3}-?\d{3}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("MUNIC", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 20);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match("/(\d{4}-\d{1})|(\d{5})/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 200);
                    $sub_arr = explode("\t\n", $sub_str);

                    foreach ($sub_arr as $str) {
                        if (preg_match("/^\d{4}$/", $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") == false
                ) {
                    if (sizeof($arr = explode("MUNIC.", strtoupper($text))) > 1) {
                        $substr = substr($arr[1], 0, 15);

                        if (preg_match("/\d{5,6}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);
                        $sub_arr = explode("\t\n", $sub_str);

                        foreach ($sub_arr as $str) {
                            if (preg_match("/^\d{3,6}$/", $str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    } else if (sizeof($arr = explode("MUNIC.:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_arr = explode(" ", str_replace(["\n", "\t"], " ", $arr[1]));

                        foreach ($sub_arr as $str) {
                            if (preg_match("/^\d{4}$/", $str, $matches)) {
                                $res = $matches[0];
                                break;
                            }
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("03-RECEITA", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 25);

                        if (preg_match("/\d{1,4}\-\d{1}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 100);
                    $sub_arr = explode("\t", $sub_str);

                    foreach ($sub_arr as $str) {
                        if (preg_match("/^\n?\d{3}$/", $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("TOTALAPAGAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 175);

                    if (preg_match("/\d{5}-\d{3}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("CEP", strtoupper($text));
                    $sub_str = substr($arr[2], 0, 50);

                    if (preg_match("/\d{7,9}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $arr = explode("\n", str_replace("\N", "\n", strtoupper($text)));

                    foreach ($arr as $str) {
                        if (preg_match("/^\d{6}-\d{1,}/", $str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("\t", $text);

                    foreach ($arr as $str) {
                        if (preg_match("/(^\d{5}-?\d{3}$)|(^\d{7}$)/", $str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                return str_replace(array(".", "-", " ", "\n", "\t"), "", $res);

                //Retorna o CNPJ da branch, caso esteja na guia. Se o CNPJ da branch nao estiver na guia, retorna o CNPJ da Click Matriz
            case "cnpj_branch":
                $regExp = "/(\d{2}.\d{3}.\d{3}\/\d{4}-\d{2})|(\d{9}\.\d{2}-\d{2})/"; //Dá match em CNPJ ou Inscrição Estadual
                $res = 15121491000953; //cnpj da Click Matriz

                if (strpos(strtoupper($text), "GNRE") !== false && strpos(strtoupper($text), "CLICK") !== false) {
                    $replaced = str_replace("\n", "\t", $text);
                    $arr = explode("\t", $replaced);

                    foreach ($arr as $str) {
                        if (preg_match("/^\d{2}.\d{3}.\d{3}\/\d{4}-\d{2}$/", trim($str), $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Se guia for em nome da Click Rodo
                if (strpos(strtoupper($text), "CLICK") !== false && strpos(strtoupper($text), "RODO") !== false) {
                    //Dar match em CNPJ ou Inscricao Estadual
                    if (preg_match("/(\d{2}.\d{3}.\d{3}\/\d{4}-\d{2})|(\d{9}\.\d{2}-\d{2})/", str_replace(" ", "", $text), $matches)) {
                        $res = $matches[0];
                    }

                    //Distrito Federal
                    if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                        $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                        $sub_str = substr($arr[1], 0, 120);

                        if (preg_match("/\d{14}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }

                    //Rio de Janeiro
                    if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                        $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                        $sub_str = substr($arr[1], 0, 200);

                        //Matches Jul/20yy or 08/20yy
                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                return str_replace(array("/", ".", "-"), "", $res);

                //Retorna o CNPJ da Secretaria da Fazenda do Estado da guia
            case "cnpj_sefaz":
                $res = "";
                //CNPJ da Secretaria da Fazenda de cada estado
                $cnpj = array(
                    "AC" => "04.034.484/0001-40",
                    "AL" => "12.200.192/0001-69",
                    "AM" => "04.312.377/0001-37",
                    "AP" => "00394577000125",
                    "BA" => "13.937.073/0001-56",
                    "CE" => "07.954.597/0001-52",
                    "ES" => "27.080.571/0001-30",
                    "GO" => "01.409.655/0001-80",
                    "MA" => "03.526.252/0001-47",
                    "MT" => "03.507.415/0005-78",
                    "MS" => "02.935.843/0001-05",
                    "MG" => "16.907.746/0001-13",
                    "PI" => "06.553.556/0001-91",
                    "RO" => "05.599.253/0001-47",
                    "RR" => "16.723.250/0001-90",
                    "SC" => "82.951.310/0001-56",
                    "SP" => "46.377.222/0001-29",
                    "SE" => "13.128.798/0011-75",
                    "TO" => "25.043.514/0001-55",
                    "DF" => "00.394.684/0001-53",
                    "PA" => "5.054.903/0001-79",
                    "PB" => "08.761.132/0001-48",
                    "PE" => "10.572.014/0001-33",
                    "PR" => "76.416.890/0017-46",
                    "RN" => "24.519.654/0001-94",
                    "RS" => "87.958.674/0001-81"
                );

                $uf = $this->matchFormData($text, "state");

                $res = $cnpj[trim($uf)];

                return str_replace(array(".", "/", "-"), "", $res);

                //Retorna o Estado da guia, ou UF favorecida caso seja uma GNRE
            case "state":
                $res = "";
                //Usado para garantir que o estado capturado da guia eh o resultado esperado
                $arr_states = ["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"];

                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    if (sizeof($arr = explode("UFFAVORECIDA:", strtoupper(str_replace([" ", "\n", "\t"], "", $text)))) > 1) {
                        $substr = substr($arr[1], 0, 10);

                        //Check if there's a match and if the match is the expected result
                        if (preg_match("/\w{2}/", $substr, $matches) && in_array($matches[0], $arr_states, true)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("DADOSDODESTINAT", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 4, 20);

                        if (preg_match("/\w{2}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOACRE")  !== false) {
                    $res = "AC";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $res = "AM";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $res = "AP";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GO") !== false) {
                    $res = "BA";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $res = "CE";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEALAGOAS") !== false) {
                    $res = "AL";
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $res = "DF";
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $res = "ES";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $res = "MG";
                }

                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $res = "TO";
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    $res = "PA";
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    $res = "PB";
                }

                //Paraná
                if (strpos(strtoupper(str_replace([" "], "", $text)), "ESTADODOPARANÁ") !== false) {
                    $res = "PR";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $res = "PE";
                }

                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $res = "PI";
                }

                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $res = "MT";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $res = "MS";
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $res = "SP";
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $res = "RJ";
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $res = "RS";
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $res = "RN";
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    $res = "RN";
                }

                return $res;

            case "uf_sefaz":
                $res = "";

                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $arr = explode("DADOS DO DESTINAT", strtoupper($text));

                    if (preg_match("/\s\w{2}\s/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }

                    $res = str_replace(array("\n", "\t"), "", $res);
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOACRE") !== false) {
                    $res = "AC";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $res = "AM";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $res = "AP";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GO") !== false) {
                    $res = "BA";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $res = "CE";
                }

                if (strpos(strtoupper($text), "ESTADO DE ALAGOAS")) {
                    $res = "AL";
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $res = "DF";
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $res = "ES";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $res = "MG";
                }

                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $res = "TO";
                }

                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $res = "MT";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $res = "MS";
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    $res = "PA";
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    $res = "PB";
                }

                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $res = "PE";
                }

                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $res = "";
                    $arr = explode("CEP", strtoupper($text));

                    if (preg_match("/\w{2}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $res = "SP";
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $res = "RJ";
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $res = "RS";
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $res = "RN";
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    $res = "RN";
                }

                return $res;

                //Retorna o nome do contribuinte da guia
            case "sender_name":
                $res = "";
                //Dá match em vários formatos comuns de nomes
                $regExp = "/^(\w{2}.\s{0,}\w{2,}\s{0,}\w{2,})|(\w{1}\.\w{3,})|(\w{1}\.\w{1}\.\w{3,})|(\w{1}\.\w{2}\s{1,}\w{1}\s{1,}\w{3,})|(\w{3,}\s{1,}\w{2}\s{1,}\w{2})|(\w{3,}\s{1,}\d{1,})|(\w{1}\s{1,}\w{2,}\s{1,}\w{1}\s{1,}\w{3,})|(\w{1}\s{1,}\w{1}\s{1,}\w{3,})|(\w{1}\.\w{1}\.\w{1}\.\w{3,})|(\w{1}\.\s{1,}\w{3,})|(\w{1}\.\w{1}\.\s{1,}\w{2,})|(\w{3,}\s{1,}[S]\s{1,}\/\s{1,}[A])|(\w{3,}\s{1,}\w{3,})|(\w{3,})/";
                //Alternativa a $regExp
                $regExpAlt = "/([0-9]{0,1}[A-ZÁÉÍÓÚÃÕÂÊÎÔÛÀÈÌÒÙÄËÏÖÜÇ&]{1,}\.?\s?-?\s?)([0-9]{2}\s?)?([A-ZÁÉÍÓÚÃÕÂÊÎÔÛÀÈÌÒÙÄËÏÖÜÇ&]{1,}\,?\.?\s?\-?\s?\,?){1,}(\/A)?/";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $arr = explode("- BANCO", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 100);
                    $sub_arr = explode("\t", $sub_str);

                    //"\t" existe na substring, então recolher o dado através desse meio
                    if (sizeof($sub_arr) > 1) {
                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, $str, $matches)) {
                                $res = $str;
                                break;
                            }
                        }
                    } else if ($res == "") { //"\t" não existe na substring, então recolher o dado através de outro método
                        $arr = explode("BANCO", strtoupper($text));
                        $sub_arr = explode("\n", $arr[1]);

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, $str, $matches)) {
                                $res = $str;
                                break;
                            }
                        }
                    }
                }

                //Acre
                if (strpos(strtoupper($text), "ESTADO DO ACRE")) {
                    if (sizeof($arr = explode("SUJEITO PASSIVO", strtoupper($text))) > 1) {
                        $substr = substr($arr[1], 60, 200);

                        if (preg_match($regExpAlt, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = explode("ICMS", strtoupper($text));
                        $sub_str = substr($arr[2], 58);
                        $sub_arr = explode("\n", $sub_str);

                        foreach ($sub_arr as $str) {
                            //Matches a name with 3 or more characters followed by a space
                            if (preg_match($regExp, $str, $matches)) {
                                //If there's a match, get the whole string and break the loop
                                $res = $str;
                                break;
                            }
                        }
                    }

                    $res = str_replace(array("\n", "\t"), "", $res);
                }

                //Alagoas
                if (strpos(str_replace([" ", "\n"], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    //Replace \n with ":::" to avoid capturing the wrong text in preg_match()
                    $arr = explode("NOME:", str_replace(["\n"], ":::", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 200);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    
                    if (sizeof($arr = explode("CONTRIBUINTE", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[2], 0, strpos($arr[2], "MULTA"));

                        if (preg_match($regExpAlt, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Amapá
                if (strpos(strtoupper($text), "ESTADO DO AMAP")) {
                    $arr = explode("SOCIAL", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 50);

                    //Matches a name with 3 or more characters followed by a space
                    if (preg_match($regExp, $sub_str, $matches)) {
                        //If there's a match, get the whole string and break the loop
                        $res = $matches[0];
                    }

                    $res = str_replace(array("\n", "\t"), "", $res);
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("NOME", strtoupper($text));
                    $sub_str = substr($arr[1], 24, 100);
                    $sub_arr = explode("\t\n", $sub_str);

                    foreach ($sub_arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $str;
                            break;
                        }
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    if (sizeof($arr = explode("7 - MULTA", str_replace("\n", "::", strtoupper($text)))) > 1) {
                        $substr = substr($arr[2], 0, 250);
                        $substr = preg_replace("/\d{1,2}\,\d{2}/", " ", $substr);
                        
                        if (preg_match($regExpAlt, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("CONTRIBUINTE", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 75);
                        $sub_arr = explode("\n", $sub_str);

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExpAlt, $str, $matches)) {
                                $res = $matches[0];
                                break;
                            }
                        }
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("CEP", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 75);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("CONTRIBUINTE:", strtoupper($text));
                    $sub_str = substr($arr[1], 0, strpos(str_replace(" ", "", $arr[1]), "AUTENTICA"));

                    //Matches CNPJ format followe by an optional name
                    if (preg_match("/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\s?-?\s?(([A-Z]{1,}\s?-?\s?)([A-Z]{1,}\s?){1,})?/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGO DE BARRAS", strtoupper($text));
                    $sub_str = substr($arr[1], 20, 100);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    if (sizeof($arr = explode("CONTRIBUINTE", strtoupper($text))) > 1) {
                        $substr = substr($arr[1], 0, 150);

                        if (preg_match($regExpAlt, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                    
                }

                //Mato Grosso do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $arr = explode("NOME", strtoupper($text));
                    $sub_str = substr($arr[0], -50);
                    $sub_arr = explode("\n", $sub_str);

                    foreach ($sub_arr as $str) {
                        //Matches a name with 3 or more characters followed by a space
                        if (preg_match($regExp, $str, $matches)) {
                            //If there's a match, get the whole string and break the loop
                            $res = $str;
                        }
                    }

                    $res = str_replace(array("\n", "\t"), "", $res);
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[2], 0, 100);

                        if (preg_match($regExpAlt, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("TRIBUTO.:", strtoupper($text))) > 1) {
                        $sub_arr = explode("\n", $arr[1]);

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExpAlt, $str, $matches)) {
                                //array_push($match, $matches);
                                $res = $matches[0];
                                break;
                            }
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("09 - PARCELA", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);

                        if (preg_match($regExpAlt, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("DAE", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 75);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("TOTAL A PAGAR", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 100);
                    $sub_arr = explode("\n", $sub_str);

                    foreach ($sub_arr as $str) {
                        if (preg_match($regExpAlt, $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("NOME:", strtoupper($text));
                    $sub_str = substr($arr[2], 0, 75);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("CONTRIBUINTE", str_replace(["\n", "\t"], " ", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 100);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }

                    //Removes the string "- CNPJ", if present
                    if (strpos($res, "- CNPJ") !== false) {
                        $res = str_replace("- CNPJ", "", $res);
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("TELEFONE", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 15, 200);

                        if (preg_match($regExpAlt, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Tocantins
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOTOCANTINS")) {
                    if (sizeof($arr = explode("ORIGEM DO CONTRIBUINTE:", str_replace(["\N", "\T"], ["\n", "\t"], strtoupper($text)))) > 1) {
                        $substr = substr($arr[3], 0, 250);
                        $substr = preg_replace("/\d{1,2}\,\d{2}/", " ", $substr);

                        if (preg_match_all($regExpAlt, $substr, $matches)) {
                            $res = $matches[0][1];
                        }
                    } else if (sizeof($arr = explode("PROTOCOLO:", strtoupper($text))) > 1) {
                        $substr = substr($arr[3], 11, 150);

                        //var_dump($substr);die;
                        if (preg_match($regExpAlt, $substr, $matches)) {
                            $res = $matches[0];
                        }

                        //If above method didn't work, try again with a different method
                        if ($res == "") {
                            $sub_arr = explode("\n", $arr[3]);

                            foreach ($sub_arr as $str) {
                                if (preg_match($regExp, $str, $matches)) {
                                    //If there's a match, get the whole string and break the loop
                                    $res = $str;
                                    break;
                                }
                            }
                        }
                    } else if (sizeof($arr = explode("TSE", strtoupper($text))) > 1) {
                        $sub_arr = explode("\n", $arr[3]);
                        $count = 1;

                        foreach ($sub_arr as $str) {
                            if (preg_match($regExp, $str, $matches)) {
                                //If there's a match, get the whole string and break the loop
                                $res = $str;
                                if ($count === 2) break; //Get the second match
                                $count++;
                            }
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("VENCIMENTO", strtoupper($text));
                    $sub_arr = explode("\t", $arr[1]);
                    $count = 1;

                    foreach ($sub_arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            //If there's a match, get the whole string and break the loop
                            if ($count == 2) {
                                $res = trim($str);
                                break;
                            }
                            $count++;
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("SOCIAL", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExpAlt, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                return trim(str_replace(["\n", "\t"], "", $res));

                //Retorna o CNPJ do contribuinte
            case "seller_cnpj":
                $res = "";
                $regExp = "/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})|(\d{3}\.\d{3}\.\d{3}\-\d{2})|(\d{2}\.\d{3}\.\d{3}-\d{1})/"; //Dá match em CNPJ, CPF ou Inscrição Estadual
                $regExpAlt = "/^\d{2}.\d{3}.\d{3}\/\d{4}-\d{2}$/";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $replaced = str_replace("\n", "\t", $text);
                    $arr = explode("\t", $replaced);

                    foreach ($arr as $str) {
                        if (preg_match($regExpAlt, trim($str), $matches)) {
                            $res = $matches[0];
                        }
                    }

                    //If Previous mehtod did not work
                    if (empty($res)) {
                        $arr = explode("-BANCO", str_replace(" ", "", strtoupper($text)));
                        $sub_str = substr($arr[1], 0, 60);

                        if (preg_match("/\d{9}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Acre
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOACRE") !== false) {
                    if (sizeof($arr = explode("SUJEITOPASSIVO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $substr = substr($arr[1], 0, 100);

                        if (preg_match($regExp, $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                    
                    
                    
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        if (preg_match($regExpAlt, trim($str), $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Mato Grosso e Mato Grosso do Sul
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false
                ) {
                    $arr = explode("CNPJ", strtoupper($text));

                    if (preg_match("/(\d{2}.\d{3}.\d{3}\/\d{4}-\d{2})|(\d{14})/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    if (sizeof($arr = explode("CHAVENF-E:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        //var_dump($arr);die;
                        $substr = substr($arr[1], 6, 100);

                        if (preg_match("/^\d{14}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = explode("DIGODOCONTRIBUINTE", strtoupper(str_replace(" ", "", $text)));

                        if (preg_match("/(\d{2}.\d{3}.\d{3}\/\d{4}-\d{2})|(\d{14})/", $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("ESTADUAL", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/(\d{2}.\d{3}.\d{3}\/\d{4}-\d{2})|(\d{14})/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("TOTAL:", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], -50);
                        
                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("CNPJ", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match("/\d{3}\.\d{3}\.\d{3}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    if (preg_match("/\d{2}.\d{3}.\d{3}\/\d{4}-\d{2}/", str_replace(" ", "", $text), $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 120);

                    if (preg_match("/\d{14}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("CONTRIBUINTE:", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    //Matches CNPJ format, often found in ES guides instead of a name
                    if (preg_match("/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("CTE:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 6, 100);

                        if (preg_match("/\d{14}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("CNPJ.:", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("/CGC/CPF", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        if (preg_match("/^\d{2}.?\d{3}.?\d{3}\/?\d{4}-?\d{2}$/", trim($str), $matches)) {
                            $res = $matches[0];
                            break;
                        }

                        //If the above expression fails to match anything, try to match a number with 13 digits.
                        if (preg_match("/^\d{13}$/", trim($str), $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO")) {
                    $arr = explode("CALCULAR", str_replace(" ", "", $text));
                    $sub_str = substr($arr[1], 0, 100);

                    if (preg_match("/\d{7}-\d{2}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("CNPJ:", str_replace([" ", "\n", "\t"], "", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("TELEFONE", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 200);

                        if (preg_match_all("/\d{14}/", $sub_str, $matches)) {
                            $res = $matches[0][1];
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("OS:", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 200);

                    //Matches Jul/20yy or 08/20yy
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Tocantins
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOTOCANTINS") !== false) {
                    if (preg_match($regExp, str_replace(" ", "", $text), $matches)) {
                        $res = $matches[0];
                    }
                }

                return str_replace(array(".", "/", "-"), "", $res);

                //Retorna o código da receita
            case "revenue_code":
                $res = "";

                //GNREs
                if (strpos(strtoupper($text), "GNRE") !== false) {
                    //GNRE Múltipla
                    if (strpos(strtoupper(str_replace(" ", "", $text)), "ITENSDAGNRE") !== false) {
                        $arr = explode("VALORTOTAL", strtoupper(str_replace(" ", "", $text)));

                        if (preg_match("/\d{3,}/", $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    } else { //GNRE Tradicional
                        $arr = explode("DADOS", strtoupper($text));

                        if (preg_match("/\d{3,}/", $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Acre
                if (strpos(strtoupper($text), "ESTADO DO ACRE")) {
                    $arr = explode("ICMS", strtoupper($text));

                    if (preg_match("/\d{5}/", trim($arr[1]), $matches)) {
                        $res = $matches[0];
                    }
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("JUROS:", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[2], 0, 15);

                        if (preg_match("/\d{4,5}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("TRIBUTO", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{4,}/", trim($arr[1]), $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("RECEITA", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{4,}/", $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("DIGODARECEITA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match("/\d{3,}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $arr = explode("RECEIT", strtoupper($text));

                    if (preg_match("/\d{4,}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("AUTENTICARNOVERSO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match("/\d{3,6}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("RECEITA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[2], 0, 15);

                    //Matches CNPJ format, often found in ES guides instead of a name
                    if (preg_match("/\d{3}-\d{1}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("RECEITA", strtoupper($text));
                    $sub_str = substr($arr[2], 0, 15);

                    if (preg_match("/\d{4}-\d{1}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $arr = explode("DIGO", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{3,4}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $arr = explode("DIGODOTRIBUTO", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{3,}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("03 - RECEITA", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 50);

                        if (preg_match_all("/\d{4}/", $sub_str, $matches)) {
                            $res = !empty($matches[0][1]) ? $matches[0][1] : $matches[0][0];
                        }
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("DIGO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[2], 0, 20);

                    if (preg_match("/\d{3,6}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("CORRIGIDO(R$)", str_replace(" ", "", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match("/\d{4}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("BITO(S)", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 200);

                        if (preg_match("/\d{4}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Tocantins
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOTOCANTINS") !== false) {
                    if (sizeof($arr = explode("ICMSCONSUMIDORFINALN", str_replace([" "], "", strtoupper($text)))) > 1 ||
                        sizeof($arr = explode("ICMSDIFERENCIALDEAL", str_replace([" "], "", strtoupper($text)))) > 1
                    ) {

                        if (preg_match("/\d{3,4}$/", $arr[0], $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = str_replace(["\t", "\T", "\N"], [" ", " ", "\n"], strtoupper($text));
                        $arr = explode("\n", $arr);

                        foreach ($arr as $str) {
                            if (preg_match("/^\d{3}$/", trim($str), $matches)) {
                                $res = $matches[0];
                            }
                        }

                        //Above method did not work. Try again with another method
                        if (empty($res)) {
                            $arr = explode("ICMS", strtoupper($text));
                            
                            if (preg_match("/\d{3}$/", $arr[0], $matches)) {
                                $res = $matches[0];
                            }
                        }
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match("/\d{4}-\d{1}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("12-MULTA", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match("/\d{4}-\d{1}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("DIGO DA RECEITA", strtoupper($text));

                    if (preg_match("/\d{6}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 175);
                    $sub_arr = explode("\t", $sub_str);

                    foreach ($sub_arr as $str) {
                        if (preg_match("/^\d{5}-\d{1}$/", $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("DIGOGNRE", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match("/\d{5}-\d{1}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                return str_replace(array("\t", "-"), "", $res);

                //Retorna a data de vencimento da guia
            case "due_day":
                $res = "";
                $regExp = "/\d{1,2}\/\d{1,2}\/20\d{2}/";

                //GNREs
                if (strpos(strtoupper($text), "GNRE") !== false) {
                    //GNRE Múltipla
                    if (strpos(strtoupper(str_replace(" ", "", $text)), "ITENSDAGNRE") !== false) {
                        $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));

                        if (preg_match($regExp, $arr[3], $matches)) {
                            $res = $matches[0];
                        }
                    } else { //GNRE Tradicional
                        $arr = explode("JUROS", strtoupper($text));

                        if (preg_match($regExp, trim($arr[2]), $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Guias de Estado
                //Acre
                if (strpos(strtoupper(str_replace(["\n", "\t", " "], "", $text)), "ESTADODOACRE") !== false) {
                    if (sizeof($arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $substr = substr($arr[1], 0, 150);
    
                        if (preg_match_all($regExp, $substr, $matches)) {
                            $res = $matches[0][1];
                        }
                    } else {
                        $arr = explode("ICMS", strtoupper($text));

                        if (preg_match($regExp, trim($arr[1]), $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $arr = explode("VENCTO.", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Alagoas e Mato Grosso do Sul
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEALAGOAS") !== false ||
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false
                ) {
                    $arr = explode("VENCIMENTO", strtoupper($text));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, trim($arr[1]), $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $arr = explode("DATAVENCIMENT", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match($regExp, trim($arr[1]), $matches)) {
                        $res = $matches[0];
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    //Matches CNPJ format, often found in ES guides instead of a name
                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 275);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 60);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("DATAEMISS", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("DATADEVENCIMENTO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match_all($regExp, $sub_str, $matches)) {
                            $res = $matches[0][0];
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 175);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("DATAVENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[2], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("VENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 15);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("VENCIMENTO:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 30);

                        if (preg_match($regExp, $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS") || strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("\t\n", $text);

                    foreach ($arr as $str) {
                        //If there's a match, get the first match then break the loop
                        if (preg_match($regExp, trim($str), $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("DATADEVENCIMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("TOTALAPAGAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                return strToDate($res);

                //Retorna o dia do pagamento da guia, no caso hoje pois a guia ainda nao foi paga
            case "payment_day":
                $res = "";

                $dtm = ws::qfield("SELECT NOW()");
                $date = explode(" ", $dtm);
                $res = $date[0];

                return $res;

                //Retorna o valor do FECP / FECOP
            case "value_fecp":
                $res = "";

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    if (strpos(strtoupper($text), "FECP") || strpos(strtoupper($text), "FECOP")) {
                        $arr = explode("NF-E", strtoupper($text));

                        if (preg_match("/(\d{1,}.)?\d{1,},\d{2}/", $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Retorna valores com "." em vez de ","
                return preg_replace("/^(\d{1,})?.?(\d{1,}),(\d{2})$/", "$1$2.$3", $res, 1);

                //Retorna o código de barras da guia
            case "bar_code":
                $res = "";
                $regExp = "/(\d{11,}\-\d{1}\s?){1,}/";

                //GNREs
                if (
                    strpos(strtoupper($text), "GNRE") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                ) {
                    $arr = explode("\t\n", $text);

                    foreach ($arr as $str) {
                        if (preg_match("/\d{11}\s{1,}?\d{1}\s{1,}?\d{11}\s{1,}?\d{1}\s{1,}?\d{11}\s{1,}?\d{1}\s{1,}?\d{11}\s{1,}?\d{1}/", trim($str), $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Guias de Estado
                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS")) {
                    $sub_str = substr(str_replace([" "], "", $text), 0, 75);

                    if (preg_match("/\d{48}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("PROCESSAMENTO", strtoupper(str_replace(" ", "", $text)));
                    $sub_arr = explode("\t\n", $arr[1]);

                    foreach ($sub_arr as $str) {
                        if (preg_match("/\d{11}-\d{1}\d{11}-\d{1}/", trim($str), $matches)) {
                            $res = $str;
                            break;
                        }
                    }
                }

                //Acre
                if (strpos(str_replace([" ", "\n", "\t"], "", strtoupper($text)), "ESTADODOACRE") !== false) {
                    //A string original eh "S?RIETADNÚMERO", mas quebrei em "RIETADN" para evitar
                    //problemas com acentuacao
                    if (sizeof($arr = explode("RIETADN", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $substr = substr($arr[1], 0, 150);

                        if (preg_match("/\d{48,}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = explode("\n", str_replace(" ", "", $text));

                        foreach ($arr as $str) {
                            if (preg_match("/\d{47,}/", $str, $matches)) {
                                $res = $matches[0];
                                break;
                            }
                        }
                    }

                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("MODELO", $text);

                    if (preg_match($regExp, $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $sub_str = substr(strtoupper(str_replace([" ", "\n"], "", $text)), 0, 250);
                    $sub_arr = explode("\t", $sub_str);

                    foreach ($sub_arr as $str) {
                        //codigo de barras nas guias de BA vem particionado em "\t" e "\n"
                        //Entao foi necessario quebrar a string nas particoes e concatenar o resultado de preg_match
                        if (preg_match("/\d{11,}/", $str, $matches)) {
                            $res .= $matches[0];
                        }
                    }
                }


                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        if (preg_match("/\d{47,}/", str_replace(" ", "", $str), $matches)) {
                            $res = $str;
                            break;
                        }
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("CEP", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 150);

                    if (preg_match("/\d{47,}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("JUROS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 100);

                    if (preg_match("/\d{47,}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $arr = explode("VIACONTRIBUINTE", str_replace(" ", "", $text));

                    foreach ($arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Mato Grosso do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $arr = explode("\n", str_replace(" ", "", $text));

                    foreach ($arr as $str) {
                        if (preg_match($regExp, $str, $matches)) {
                            $res = $matches[0];
                            break;
                        }
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    $arr = explode("DATAEMISS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 100);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }

                    //Try another method if the above does not work
                    if (empty($res)) {
                        $arr = explode("DATA EMISS", strtoupper($text));
                        
                        if (preg_match("/\d{12}\s{1,}\d{12}\s{1,}\d{12}\s{1,}\d{12}/", $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("29-MATR", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 150);

                        if (preg_match("/\d{11}\-\d{1}\d{11}\-\d{1}\d{11}\-\d{1}\d{11}\-\d{1}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Pernambuco
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEPERNAMBUCO") !== false) {
                    $arr = explode("CALCULAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 250);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $sub_str = substr(str_replace(" ", "", $text), 0, 100);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("DIGOPIX", strtoupper(str_replace([" ", "\n", "\t"], "", $text)));
                    $sub_str = substr($arr[1], 0, 100);

                    

                    if (preg_match("/^\d{11}-\d{1}\d{11}-\d{1}\d{11}-\d{1}\d{11}-\d{1}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("DIGOPAGAMENTOBOLETO", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        if (preg_match($regExp, $arr[1], $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    $arr = explode("\n", $text);
                    $count = 1;

                    foreach ($arr as $str) {
                        if (preg_match("/\d{11}-\d{1}\s{1,}\d{11}-\d{1}\s{1,}\d{11}-\d{1}/", trim($str), $matches)) {
                            if ($count == 2) $res = $str;

                            $count++;
                        }
                    }

                    //If the above method is not successful, try another method
                    if (empty($res)) {
                        if (preg_match("/(\d{11}\-\d{1}){3,4}/", str_replace(" ", "", $text), $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("LINHA DIGIT", strtoupper($text));
                    $sub_arr = explode("\n", $arr[1]);

                    foreach ($sub_arr as $str) {
                        if (preg_match("/\d{11}-\d{1}/", trim($str), $matches)) {
                            $res = $str;
                            break;
                        }
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("SOCIAL", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 85);

                    if (preg_match("/(\d{11}\-\d{1}){1,}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("APURA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 200);

                    if (preg_match($regExp, $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                return str_replace(array("-", " "), "", $res);

            case "date_generated":
                $res = "";

                $dtm = ws::qfield("SELECT NOW()");
                $date = explode(" ", $dtm);
                $res = $date[0];

                return $res;

                //Retorna o tipo da receita. Utilizado no campo de observacao do csv. No caso da LASA, tax_type precisar ser "ICMS", independente da guia.
            case "tax_type":
                $res = "ICMS";

                return $res;

                //Retorna o número da nota fiscal da guia
            case "nf_number":
                $res = "";

                //GNREs
                if (strpos(strtoupper($text), "GNRE") !== false) {
                    //GNRE Múltipla
                    if (strpos(strtoupper(str_replace(" ", "", $text)), "ITENSDAGNRE") !== false) {
                        $arr = explode("NFE", strtoupper(str_replace(" ", "", $text)));

                        if (preg_match("/\d{8,}/", $arr[1], $matches)) {
                            //Remove any zeroes from the beginning of the string
                            $res = preg_replace("/^[0]{1,}/", "", $matches[0]);
                            $res = $res;
                        }
                    }
                }

                //Amazonas
                if (strpos(str_replace(" ", "", strtoupper($text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("CHAVENF-E:", str_replace([" ", "\n", "\t"], "", strtoupper($text)));
                    $substr = substr($arr[1], 25, 25);

                    if (preg_match("/\d{9}/", $substr, $matches)) {
                        $res = preg_replace("/^[0]{0,}/", "", $matches[0]);
                    }
                }

                //Ceara
                if ( strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    if (sizeof($arr = explode("NOTASFISCAIS:", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[2], 0, 25);

                        if (preg_match("/\d{2,6}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Mato Grosso do Sul
                if ( strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $arr = explode("FISCAIS", $text);

                    if (preg_match("/\d{3,}/", str_replace(" ", "", $arr[1]), $matches)) {
                        $res = $matches[0];
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("DOCS:", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 50);

                        if (preg_match("/\d{1,9}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "NFE--") !== false) {
                        $arr = explode("NFE -  -", strtoupper($text));
                        $sub_str = substr($arr[1], 0, 20);

                        if (preg_match("/\d{2,9}/", $sub_str, $matches)) {
                            $res = preg_replace("/^[0]{1,}/", "", $matches[0]);
                        }
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("TELEFONE", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 60);

                        //Remover rodape da pagina, se presente
                        $sub_str = preg_replace("/PáGINA\s{1,}\d{1,2}\s{1,}DE\s{1,}\d{1,2}/", "", $sub_str);

                        if (preg_match("/\d{2,9}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Tocantins
                if (strpos(strtoupper($text), "ESTADO DO TOCANTINS")) {
                    if (sizeof($arr = explode("DANFES", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 75);

                        if (preg_match("/(\d{3,}\.\d{3,}\.\d{3,})|(\d{3,}\.\d{3,})|(\d{1}\.\d{3,}\.\d{3,})|(\d{1,}\.\d{3,})|(\d{3,})/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("DANFE", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 75);

                        if (preg_match("/(\d{3,}\.\d{3,}\.\d{3,})|(\d{3,}\.\d{3,})|(\d{1}\.\d{3,}\.\d{3,})|(\d{1,}\.\d{3,})|(\d{3,})/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("REF.", strtoupper($text))) > 1) {
                        //Get a substring from the string $arr[1] to avoid mismatching nf_number
                        $sub_str = substr($arr[1], 0, 75);

                        if (preg_match("/(\d{3,}\.\d{3,}\.\d{3,})|(\d{3,}\.\d{3,})|(\d{1}\.\d{3,}\.\d{3,})|(\d{1,}\.\d{3,})|(\d{3,})/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("ICMS", strtoupper($text))) > 1) {
                        $sub_str = substr($arr[1], 0, 50);
                        $count = 1;

                        if (preg_match("/(\d{3,}\.\d{3,}\.\d{3,})|(\d{3,}\.\d{3,})|(\d{1}\.\d{3,}\.\d{3,})|(\d{1,}\.\d{3,})|(\d{3,})/", $sub_str, $matches)) {
                            $res = $matches[0];
                            if ($count == 2) break;
                            $count++;
                        }
                    }

                    //If none of the above methods worked, try another method
                    if (empty($res)) {
                        $arr = explode("CHAVEDEACESSODANFE:", str_replace([" ", "."], "", strtoupper($text)));
                        $sub_arr = explode("\n", $arr[1]);
                        
                        if (count($sub_arr) > 1) {
                            //nf_number could be at position 1 or 2 of sub_arr
                            $aux = (!empty($sub_arr[1])) ? explode(",", $sub_arr[1]) : explode(",", $sub_arr[2]);

                            if (preg_match("/^\d{3,6}$/", $aux[0], $matches)) {
                                $res = $matches[0];
                            }
                        } else {
                            $sub_str = substr($sub_arr[0], 44); //Remover os 44 digitos da chave da nf
                            if (preg_match("/\d{2,9}/", $sub_str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    }
                }

                //Piauí
                if (strpos(strtoupper($text), "ESTADO DO PIAU")) {
                    $arr = explode("NF-E", strtoupper($text));

                    if (preg_match("/\d{3,}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                return str_replace(array(".", ","), "", $res);

                //Retorna a observação da guia no seguinte formato:
                //Ex.: ICMS - GNRE [REFERÊNCIA] CARRO PARADO VENC [DATA DE VENCIMENTO] - PDF DOC  [NÚMERO DO PDF]  - SELLER CPNJ [CNPJ DO CONTRIBUINTE] - NF [NÚMERO DA NOTA FISCAL] - Nome [NOME DO CONTRIBUINTE] - PEDIDO
            case "observation":
                $res = "";

                $tax_type = $this->matchFormData($text, "tax_type");
                $doc_type = $this->matchFormData($text, "doc_type");
                $doc_number = $this->matchFormData($text, "doc_number");
                $seller_cnpj = $this->matchFormData($text, "seller_cnpj");
                $sender_name = $this->matchFormData($text, "sender_name");
                $nf_number = $this->matchFormData($text, "nf_number");

                $today = explode(" ", ws::qfield("SELECT NOW()"))[0];
                $today_f = explode("-", $today);
                $due_date = "$today_f[2]/$today_f[1]/$today_f[0]"; //dd/mm/yyyy
                $ref = "$today_f[1]/$today_f[0]"; //Must be formated as mm/yyyy

                //Campo "observacao" difere dependendo do tipo da guia
                //Observacao para guias de ICMS Frete
                if ($doc_type === "FRETE") {
                    $doc_origem = $this->matchFormData($data, "doc_origem");

                    //Campo deve obedecer a seguinte regra: 
                    //Ex.: ICMS FRETE - COMPET [REFERENCIA (MM/AAAA)] - PDF DOC [NÚMERO DO PDF] - CTE [NÚMERO DA CTE] - Nome [NOME DA BRANCH] [CNPJ DA BRANCH]
                    $res = $tax_type . " " . $doc_type . " - COMPET " . $ref . " - PDF DOC " . $doc_number_highest_value . " - CTE " . $doc_origem . " - NOME " . $sender_name . " - FILIAL " . $seller_cnpj;
                } else { //Observacao para demais guias
                    //Campo deve obedecer a seguinte regra:
                    //ICMS - GNRE [REFERENCIA (MM/AAAA)] CARRO PARADO VENC [DATA DE VENCIMENTO] - PDF DOC  [NÚMERO DO PDF]  - SELLER CPNJ [CNPJ DO CONTRIBUINTE] - NF [NÚMERO DA NOTA FISCAL] - Nome [NOME DO CONTRIBUINTE] - PEDIDO

                    $res = $tax_type . " - " . $doc_type . " " . $ref . " CARRO PARADO VENC " . $due_date . " - PDF DOC " . $doc_number_highest_value . " - SELLER CNPJ " . $seller_cnpj . " - NF " . $nf_number . " - NOME " . $sender_name . " - PEDIDO " . str_replace(array("\t", "\n"), "", trim($order_number));
                }

                return $res;

                //Retorna 1 se a guia for GNRE e 0 caso contrario
            case "gnre":
                $res = "";

                if (strpos(strtoupper($text), "GNRE") !== false) {
                    $res = 1;
                } else {
                    $res = 0;
                }

                return $res;

                //Retorna 1 se a guia for GNRE multipla e 0 caso contrario
            case "multiple_gnre":
                $res = "";

                if (strpos(strtoupper($text), "GNRE") !== false && strpos(strtoupper(str_replace(" ", "", $text)), "ITENSDAGNRE") !== false) {
                    $res = 1;
                } else {
                    $res = 0;
                }

                return $res;

            case "upload_date":
                $res = ws::qfield("SELECT NOW()");

                return $res;

            case "agency_code":
                $res = 341; //Agency conde that was provided with the .csv layout

                return $res;

            case "upload_collaborator_id":
                $res = "";

                $query = "SELECT lasa.id FROM org_96.collaborator lasa WHERE lasa.email = 'suporte@turimsoft.com.br' AND lasa._status = 'active' LIMIT 1";
                $res = ws::qfield($query);

                return $res;

                //Retorna o NÚMERO da guia
            case "doc_number":
                $res = "";

                //GNREs
                if (strpos(strtoupper($text), "GNRE") !== false) {
                    $arr = explode("\n", $text);

                    foreach ($arr as $str) {
                        if (preg_match("/\d{16}/", $text, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Guias de Estado
                //Acre
                if (strpos(strtoupper($text), "ESTADO DO ACRE")) {
                    if (sizeof($arr = explode("STATUS", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 200);

                        if (preg_match("/\d{2}\.\d{3}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    } else {
                        $arr = explode("\n", $text);

                        foreach ($arr as $str) {
                            if (preg_match("/^\d{2}\.\d{3}$/", $str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    }
                }

                //Alagoas
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODEALAGOAS") !== false) {
                    if (sizeof($arr = explode("DOCUMENTODEARRECADA", str_replace([" "], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 50);

                        if (preg_match("/\d{9,10}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS")) {
                    $arr = explode("CONTROLE", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{13,}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Amapá
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAP") !== false) {
                    $arr = explode("REFER", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{25,}/", $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Bahia
                if (strpos(strtoupper(str_replace(" ", "", $text)), "SEFAZ.BA.GOV") !== false) {
                    $arr = explode("NOSSON", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match("/\d{10}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    if (sizeof($arr = explode("-NOSSON", str_replace(" ", "", strtoupper($text)))) > 1) {
                        $substr = substr($arr[1], 0, 50);

                        if (preg_match("/\d{4}\.\d{2}\.\d{7}\-\d{2}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Distrito Federal
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ECONOMIADODISTRITOFEDERAL") !== false) {
                    $arr = explode("RECEBERAT", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match("/\d{11,15}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Espírito Santo
                //If statement separado em duas condicionais para evitar problemas com acentuação em "Espírito"
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOESP") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "RITOSANTO") !== false
                ) {
                    $arr = explode("DUA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 50);

                    //Matches CNPJ format, often found in ES guides instead of a name
                    if (preg_match("/\d{10}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Minas Gerais
                if (strpos(strtoupper(str_replace(" ", "", $text)), "FAZENDADEMINASGERAIS") !== false) {
                    $arr = explode("DIGODEBARRAS", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 220);

                    if (preg_match("/\d{2}\.\d{9}-\d{2}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso
                if (
                    strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODEMATOGROSSO") !== false &&
                    strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") === false
                ) {
                    $arr = explode("COMPLEMENTARES", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{3}\/\d{2}\.\d{3}\.\d{3}-\d{2}/", $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Mato Grosso do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GROSSODOSUL") !== false) {
                    $arr = explode("REFER", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{2}\.\d{3}\.\d{3}\.\d{3}\-\d{2}/", $arr[2], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Pará
                if (
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPAR") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODOPARAN") == false
                ) {
                    if (sizeof($arr = explode("TOTAL:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match("/\d{11,13}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("MUNIC.:", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match("/\d{12}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Paraiba
                if (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODAPARA") !== false) {
                    if (sizeof($arr = explode("DAR-MOD2", str_replace([" ", "\n", "\t"], "", strtoupper($text)))) > 1) {
                        $sub_str = substr($arr[1], 0, 100);

                        if (preg_match("/\d{10}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Rio Grande do Sul
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIOGRANDEDOSUL") !== false) {
                    $arr = explode("GUIA", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 30);

                    if (preg_match("/\d{12,15}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio Grande do Norte
                if (strpos(strtoupper(str_replace(" ", "", $text)), "GOVERNODOESTADODORN") !== false) {
                    $arr = explode("NOSSON", str_replace([" ", "\n", "\t"], "", strtoupper($text)));
                    $sub_str = substr($arr[1], 6, 20);

                    if (preg_match("/\d{17}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Relatorio ICMS Rio Grande do Norte
                if (//Headers presentes em todas das paginas da consolidada
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                    strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false
                ) {
                    if (sizeof($arr = explode("VENCIMENTO:", strtoupper(str_replace([" ", "\n", "\t"], "", $text)))) > 1) {
                        $sub_str = substr($arr[0], -35);

                        if (preg_match("/\d{17}/", $sub_str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                //Tocantins
                if (strpos(str_replace(" ", "", strtoupper($text)), "ESTADODOTOCANTINS")) {
                    if (sizeof($arr = explode("ORIGEM DO CONTRIBUINTE", strtoupper($text))) > 1) {
                        $substr = substr($arr[3], 0, 250);
                        
                        if (preg_match("/\d{12}/", $substr, $matches)) {
                            $res = $matches[0];
                        }
                    } else if (sizeof($arr = explode("POROPERA", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        if (count($arr) > 1) {
                            $sub_str = substr($arr[1], 0, 30);
                            
                            if (preg_match("/\d{12}/", $sub_str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    } else if (sizeof($arr = explode("PROTOCOLO", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_arr = explode("\n", $arr[3]);

                        foreach ($sub_arr as $str) {
                            if (preg_match("/\d{12}/", $str, $matches)) {
                                $res = $matches[0];
                                break;
                            }
                        }
                    } else if (sizeof($arr = explode("ICMS", strtoupper($text))) > 1) {
                        $sub_arr = explode("\n", $arr[2]);

                        foreach ($sub_arr as $str) {
                            if (preg_match("/^\d{12}/", $str, $matches)) {
                                $res = $matches[0];
                                break;
                            }
                        }
                    }
                }

                //Piauí
                if (strpos(str_replace([" "], "", strtoupper($text)), "ESTADODOPIAU") !== false) {
                    $arr = explode("DOCUMENTODEORIGEM", str_replace([" "], "", strtoupper($text)));
                    $sub_str = substr($arr[1], 0, 50);

                    if (preg_match("/\d{13,14}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //São Paulo
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DARE-SP") !== false) {
                    $arr = explode("MERODODARE", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match("/\d{15}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("TOTALAPAGAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 150);

                    if (preg_match("/\d{25,}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                return str_replace(array(".", "-", "/", " "), "", $res);

                //Retorna o NÚMERO do documento que gerou a guia (sem pontuacao)
            case "doc_origem":
                $res = "";

                //GNREs
                if (strpos(strtoupper($text), "GNRE") !== false) {
                    //explode returns an array of size 1 if $separator is not found in $string
                    if (sizeof($arr = explode("FISCALIZA", strtoupper(str_replace(" ", "", $text)))) > 1) {
                        $sub_str = substr($arr[2], 0, 50);
                        $sub_arr = explode("\n", $sub_str);

                        foreach ($sub_arr as $str) {
                            if (preg_match("/^\d{3,}/", $str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    }

                    //If previous method did not work, try another.
                    if (empty($res)) {
                        if (sizeof($arr = explode("CTE", strtoupper(str_replace(" ", "", $text)))) > 1) {
                            $sub_str = substr($arr[1], 0, 25);

                            if (preg_match("/\d{5,}/", $sub_str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    }

                    //If previous method did not work, try another.
                    if (empty($res)) {
                        if (sizeof($arr = explode("DADOSDODESTINAT", strtoupper(str_replace(" ", "", $text)))) > 1) {
                            $sub_str = substr($arr[2], 0, 25);

                            if (preg_match("/\d{5,}/", $sub_str, $matches)) {
                                $res = $matches[0];
                            }
                        }
                    }
                }

                //Amazonas
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOAMAZONAS") !== false) {
                    $arr = explode("DOCUMENTODEORIGEM", strtoupper(str_replace(" ", "", $text)));

                    if (preg_match("/\d{11}-\d{1}/", $arr[1], $matches)) {
                        $res = $matches[0];
                    }
                }

                //Ceará
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODOCEAR") !== false) {
                    $arr = explode("CONHECIMENTO", strtoupper($text));
                    $sub_str = substr($arr[1], 0, 25);

                    if (preg_match("/\d{6,}/", $sub_str, $matches)) {
                        $res = $matches[0];
                    }
                }

                //Rio de Janeiro
                if (strpos(strtoupper(str_replace(" ", "", $text)), "ESTADODORIODEJANEIRO") !== false) {
                    $arr = explode("TOTALAPAGAR", strtoupper(str_replace(" ", "", $text)));
                    $sub_str = substr($arr[1], 0, 150);
                    $sub_arr = explode("\t", $sub_str);

                    foreach ($sub_arr as $str) {
                        if (preg_match("/^\d{8}$/", $str, $matches)) {
                            $res = $matches[0];
                        }
                    }
                }

                return str_replace(array("-"), "", $res);

                //Retorna o tipo do documento:
                //DATRNE caso documento seja Documento Auxiliar do Termo Eletronico de Retencao de Nota
                //FRETE caso o codigo de receita da guia seja de ICMS Transporte
                //GNRE caso nao seja nenhuma das duas acima, mesmo sendo guia de estado.
            case "doc_type":
                //PDF eh Documento Auxiliar do Termo Eletronico de Retencao de Nota (DATRNE)
                if (strpos(strtoupper(str_replace(" ", "", $text)), "DATRNE") !== false) {
                    $res = "DATRNE";
                } else { //PDF eh Guia de Estado ou GNRE
                    //codigos de ICMS Transporte
                    $icms_transporte = array(
                        "1139",
                        "100030",
                        "1260",
                        "100080",
                        "01156",
                        "000051",
                        "0775",
                        "1252",
                        "1015",
                        "1317",
                        "226",
                        "11371"
                    );

                    $revenue_code = str_replace([".", "-"], "", $this->matchFormData($text, "revenue_code"));
                    $multiple_gnre = $this->matchFormData($text, "multiple_gnre");

                    if (in_array($revenue_code, $icms_transporte) && !$multiple_gnre) {
                        $res = "FRETE";
                    } else {
                        $res = "GNRE";
                    }
                }

                return $res;

                //Retorna a ID da branch
            case "branch_id":
                $res = 2; //id Click Matriz

                if (strpos(strtoupper($text), "CLICK") && strpos(strtoupper($text), "RODO ENTREGAS")) {
                    if (
                        strpos(strtoupper($text), "GNRE") !== false &&
                        strpos(strtoupper(str_replace(" ", "", $text)), "DAREAVULSO") === false //Diferenciar de DARE / GNRE SP
                    ) {
                        $arr = explode("UF:", $text);

                        if (preg_match("/\w{2}/", $arr[1], $matches)) {
                            $uf = $matches[0];
                        }

                        $query = "SELECT lasa.id FROM org_96.branch lasa WHERE lasa.state = '$uf' AND lasa._status = 'active'";
                        $res = ws::qfield($query);
                    }
                }

                return $res;

            //Determina se $text eh um relatorio ICMS
            case "is_icms_report":
                $res = 0;

                if ( //Headers do relatorio ICMS
                    //Presente apenas na primeira pagina
                    (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "ESTADODORIOGRANDEDONORTE") !== false &&
                     strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "RIOICMSACOBRAR") !== false &&
                     strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "PLACA:") !== false) ||
                    //Presente nas demais paginas
                    (strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CNPJ/CPFDESTINO") !== false &&
                     strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "OSOCIALCONTRIBUINTE") !== false &&
                     strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "I.E.CONTRIBUINTE") !== false &&
                     strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVENF-E") !== false &&
                     strpos(strtoupper(str_replace([" ", "\n", "\t"], "", $text)), "CHAVEPIXGRI") !== false)
                ) {
                    $res = 1;
                }

                return $res;

                //Captura de dados de DATRNEs

            case "numero_termo":
                $arr = explode("MERODOTERMO", strtoupper(str_replace(" ", "", $text)));
                $sub_str = substr($arr[1], 1, 30);

                if (preg_match("/\d{4}\.\d{12}-\d{2}/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return $res;

            case "chave_mdfe":
                $arr = explode("CHAVEDEACESSODOMDF", strtoupper(str_replace(" ", "", $text)));
                $sub_str = substr($arr[1], 3, 50);

                if (preg_match("/\d{43,}/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return $res;

            case "numero_registro":
                $arr = explode("DEREGISTRO", strtoupper(str_replace(" ", "", $text)));
                $sub_str = substr($arr[1], 0);

                if (preg_match_all("/\d{3}\.\d{1}\.\d{9}-\d{1}/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return $res;

            case "chave_acesso":
                $arr = explode("CHAVEDEACESSO", strtoupper(str_replace(" ", "", $text)));
                $sub_str = substr($arr[2], 0);

                if (preg_match_all("/\d{43,}/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return $res;

            case "nota_fiscal":
                $arr = explode("CHAVEDEACESSO", strtoupper(str_replace(" ", "", $text)));
                $sub_str = substr($arr[2], 0);

                if (preg_match_all("/\t\d{5,}\t/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return str_replace("\t", "", $res);

            case "doc_ident":
                $arr = explode("CHAVEDEACESSO", strtoupper(str_replace(" ", "", $text)));
                $sub_str = substr($arr[2], 0);

                //Matches 000.000.000-00 or 00.000.000/0000-00
                if (preg_match_all("/(\d{3}\.\d{3}\.\d{3}-\d{2})|(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return $res;

            case "nome_empresarial":
                $arr = explode("RETIDOS", strtoupper($text));
                $sub_str = substr(strtoupper($arr[1]), 0, strpos(strtoupper($arr[1]), "TOTAL DE NOTA"));

                if (preg_match_all("/([A-Z]{1,}\s?){1,}[A-Z]{1,}/", $sub_str, $matches)) {
                    $res = $matches[0];
                }

                return $res;

            case "valor_nota":
                $arr = explode("RETIDOS", strtoupper($text));
                $sub_str = substr(strtoupper($arr[1]), 0, strpos(strtoupper($arr[1]), "TOTAL DE NOTA"));
                $sub_arr = explode("\t", $sub_str);

                foreach ($sub_arr as $str) {
                    if (preg_match_all("/(\d{1,}.)?\d{1,},\d{2}/", $str, $matches)) {
                        //Remover "," e "." dos valores do pdf
                        foreach ($matches[0] as $value) {
                            $res[] = preg_replace("/^(\d{1,})?\.?(\d{1,}),(\d{2})$/", "$1$2.$3", $value);
                        }
                    }
                }

                return $res;

            default:

                return false;
        }
    }
}