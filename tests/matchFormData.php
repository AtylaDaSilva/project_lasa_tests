<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests</title>
</head>

<body>
    <form action="matchFormData.php" method="get">
        <div>
            <input type="submit" value="Go to form">
            <input id="form_number1" name="form_number" type="number" value=<?php echo htmlspecialchars($_GET["form_number"]); ?>>
        </div>
    </form>

    <?php
    error_reporting(~E_NOTICE);

    require_once "../classes/z_collect_form_get_email.php";
    include "../guias/guias.php";

    $z = new z_collect_form_get_email;

    $collect_form = array();

    /*
    Campos a serem testados. Se precisar, adicione mais campos seguindo o padrão:
    Ex.: $collect_form[$i]->novo_campo = $z->matchFormData($data, "novo_campo");
    */

    //Guias de Estado / GNREs
    foreach ($guias as $i => $data) {
        $collect_form[$i] = new stdClass;
        $collect_form[$i]->reference = $z->matchFormData($data, "reference");
        $collect_form[$i]->due_date = $z->matchFormData($data, "due_date");
        $collect_form[$i]->value = $z->matchFormData($data, "value");
        $collect_form[$i]->fine = $z->matchFormData($data, "fine");
        $collect_form[$i]->interest = $z->matchFormData($data, "interest");
        $collect_form[$i]->total = $z->matchFormData($data, "total");
        $collect_form[$i]->type = $z->matchFormData($data, "type");
        $collect_form[$i]->city_code = $z->matchFormData($data, "city_code");
        $collect_form[$i]->cnpj_branch = $z->matchFormData($data, "cnpj_branch");
        $collect_form[$i]->cnpj_sefaz = $z->matchFormData($data, "cnpj_sefaz");
        $collect_form[$i]->state = $z->matchFormData($data, "state");
        $collect_form[$i]->uf_sefaz = $z->matchFormData($data, "uf_sefaz");
        $collect_form[$i]->sender_name = $z->matchFormData($data, "sender_name");
        $collect_form[$i]->seller_cnpj = $z->matchFormData($data, "seller_cnpj");
        $collect_form[$i]->revenue_code = $z->matchFormData($data, "revenue_code");
        $collect_form[$i]->due_day = $z->matchFormData($data, "due_day");
        $collect_form[$i]->bar_code = $z->matchFormData($data, "bar_code");
        $collect_form[$i]->tax_type = $z->matchFormData($data, "tax_type");
        $collect_form[$i]->nf_number = $z->matchFormData($data, "nf_number");
        $collect_form[$i]->gnre = $z->matchFormData($data, "gnre");
        $collect_form[$i]->multiple_gnre = $z->matchFormData($data, "multiple_gnre");
        $collect_form[$i]->agency_code = $z->matchFormData($data, "agency_code");
        $collect_form[$i]->doc_number = $z->matchFormData($data, "doc_number");
        $collect_form[$i]->doc_origem = $z->matchFormData($data, "doc_origem");
        $collect_form[$i]->doc_type = $z->matchFormData($data, "doc_type");
        $collect_form[$i]->is_icms_report = $z->matchFormData($data, "is_icms_report");
    }

    //DATRNEs
    /*foreach ($guias as $i => $data) {
        $collect_form[$i] = new stdClass;
        $collect_form[$i]->numero_termo = $z->matchFormData($data, "numero_termo");
        $collect_form[$i]->chave_mdfe = $z->matchFormData($data, "chave_mdfe");
        $collect_form[$i]->numero_registro = $z->matchFormData($data, "numero_registro");
        $collect_form[$i]->chave_acesso = $z->matchFormData($data, "chave_acesso");
        $collect_form[$i]->nota_fiscal = $z->matchFormData($data, "nota_fiscal");
        $collect_form[$i]->doc_ident = $z->matchFormData($data, "doc_ident");
        $collect_form[$i]->nome_empresarial = $z->matchFormData($data, "nome_empresarial");
        $collect_form[$i]->valor_nota = $z->matchFormData($data, "valor_nota");
    }*/

    //Literal indices
    $count = 1;

    foreach ($collect_form as $i => $form) {
        if ($count == $_GET["form_number"]) {
            echo ("==== PDF " . $i . " ====<br/><br/>");
            foreach ($form as $indx => $prop) {
                print_r($indx . " => ");
                print_r($prop);
                echo "<br/>";
            }
            echo ("<br/><br/>");
        }
        $count++;
    }

    ?>

    <form action="matchFormData.php" method="get">
        <div>
            <input type="submit" value="Go to form">
            <input id="form_number2" name="form_number" type="number" value=<?php echo htmlspecialchars($_GET["form_number"]); ?>>
        </div>
    </form>
</body>

</html>