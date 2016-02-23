<?php
/*
 * E-cidade Software Publico para Gestao Municipal
 * Copyright (C) 2014 DBSeller Servicos de Informatica
 * www.dbseller.com.br
 * e-cidade@dbseller.com.br
 *
 * Este programa e software livre; voce pode redistribui-lo e/ou
 * modifica-lo sob os termos da Licenca Publica Geral GNU, conforme
 * publicada pela Free Software Foundation; tanto a versao 2 da
 * Licenca como (a seu criterio) qualquer versao mais nova.
 *
 * Este programa e distribuido na expectativa de ser util, mas SEM
 * QUALQUER GARANTIA; sem mesmo a garantia implicita de
 * COMERCIALIZACAO ou de ADEQUACAO A QUALQUER PROPOSITO EM
 * PARTICULAR. Consulte a Licenca Publica Geral GNU para obter mais
 * detalhes.
 *
 * Voce deve ter recebido uma copia da Licenca Publica Geral GNU
 * junto com este programa; se nao, escreva para a Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307, USA.
 *
 * Copia da licenca no diretorio licenca/licenca_en.txt
 * licenca/licenca_pt.txt
 */
require_once (modification ( "libs/db_stdlib.php" ));
require_once (modification ( "libs/db_conecta_plugin.php" ));
require_once (modification ( "libs/db_sessoes.php" ));
require_once (modification ( "libs/db_utils.php" ));
require_once (modification ( "libs/db_app.utils.php" ));
require_once (modification ( "libs/db_usuariosonline.php" ));
require_once (modification ( "dbforms/db_funcoes.php" ));

$oPost = db_utils::postMemory ( $HTTP_POST_VARS );
$sMd5Arquivo = null;

$oTabRec = new cl_tabrec ();
$oArquivoRetornoArrecadacaoTributaria = db_utils::getDao ( "arquivoretornoarrecadacaotributaria" );
$oArquivoRetornoArrecadacaoTributariaPlanilhas = db_utils::getDao ( "arquivoretornoarrecadacaotributariaplanilhas" );

/*
 * Buscamos as configuracoes do plugin
 */
$oPlugin = new Plugin ( null, 'GeracaoPlanilhasArrecadacaoTributaria' );
$aConfiguracao = PluginService::getPluginConfig ( $oPlugin );

$sDiretorioArquivos = $aConfiguracao["diretorio_arquivos"];
$aExtensoesValidas = explode ( ",", $aConfiguracao ["extensoes_arquivos_validas"] );

if (isset ( $oPost->processar )) {
	
	try {
		
		db_inicio_transacao ();
		
		$iLayout = $aConfiguracao ["layout"];
		$rsLayout = db_query ( "select 1 from db_layouttxt where db50_codigo = {$iLayout} limit 1" );
		if (pg_num_rows ( $rsLayout ) == 0) {
			throw new Exception ( "Layout configurado não cadastrado" );
		}
		
		$iNumCgm = $aConfiguracao ["cgm_planilha"];
		$oCgm = new cl_cgm ();
		$oCgm->sql_record ( $oCgm->sql_query_file ( $iNumCgm, "true" ) );
		if ($oCgm->numrows == 0) {
			throw new Exception ( "Cgm configurado não cadastrado" );
		}
		
		/*
		 * Validamos os dados das configuracoes
		 */
		if (empty ( $iLayout ) || $iLayout == 0) {
			throw new Exception ( "Layout do arquivoo nao informado" );
		}
		
		if (empty ( $iNumCgm ) || $iNumCgm == 0) {
			throw new Exception ( "CGM não configurado para geração da planilha de arrecadação" );
		}
		
		/*
		 * Caso o diretório dos arquivos nao esteja informado utilizamos o comportamento default
		 * do contrario realizamos a leitura do arquivo no diretório
		 */
		if ($aConfiguracao ["diretorio_arquivos"] == "") {
			$oFile = db_utils::postMemory ( $_FILES ["arquivo"] );
		} else {
			
			$sArquivo = key ( $oPost ) . ".txt";
			$sArquivo = substr ( $sArquivo, 0, strlen ( $sArquivo ) - 4 ) . "." . substr ( $sArquivo, - 3 );
			
			$oFile = new stdClass ();
			$oFile->tmp_name = $sDiretorioArquivos . $sArquivo;
			$oFile->name = $sArquivo;
		}
		$sMd5Arquivo = md5 ( file_get_contents ( $oFile->tmp_name ) );
		
		/*
		 * Verifica se arquivo já foi importado
		 */
		$sSqlArquivoImportado = $oArquivoRetornoArrecadacaoTributaria->sql_query_file ( null, 'true', null, "md5 = '$sMd5Arquivo'" );
		$rsArquivoImportado = $oArquivoRetornoArrecadacaoTributaria->sql_record ( $sSqlArquivoImportado );
		if ($oArquivoRetornoArrecadacaoTributaria->numrows > 0) {
			throw new BusinessException ( "Arquivo já importado!" );
		}
		
		$oArquivoRetornoArrecadacaoTributaria->sequencial = null;
		$oArquivoRetornoArrecadacaoTributaria->arquivo = $oFile->name;
		$oArquivoRetornoArrecadacaoTributaria->data = date ( "Y-m-d" );
		$oArquivoRetornoArrecadacaoTributaria->md5 = $sMd5Arquivo;
		$oArquivoRetornoArrecadacaoTributaria->incluir ( null );
		if ($oArquivoRetornoArrecadacaoTributaria->erro_status == "0") {
			throw new Exception ( "Erro incluindo dados da baixa do arquivo.\nErro:" . $oArquivoRetornoArrecadacaoTributaria->erro_msg );
		}
		$iSequencialArquivoRetorno  = $oArquivoRetornoArrecadacaoTributaria->sequencial;
		
		/*
		 * Processamos as linhas do arquivo txt separando por instituicao
		 */
		
		$oLayoutTxt = new DBLayoutReader ( $iLayout, $oFile->tmp_name, false, true );
		$aLinhasArquivo = $oLayoutTxt->getLines ();
				
		$aDados = array ();
		foreach ( $aLinhasArquivo as $iIndLinha => $oDadosTxt ) {
			if ($oDadosTxt->id_registro == 0) {
				$dDataGeracao = $oDadosTxt->data_geracao;
			}
			
			if ($oDadosTxt->id_registro != 1) {
				continue;
			}
			
			$Where = "tabrec.k02_codigo = {$oDadosTxt->receita}";
			$sSqlBuscaDadosReceita = $oTabRec->sql_query_concarpeculiar ( null, "tabrec.k02_codigo     as receita, 
						                                                         orcreceita.o70_codigo as recurso,
						                                                         orcreceita.o70_instit as instituicao", null, $Where );
			$rsDadosReceita = $oTabRec->sql_record ( $sSqlBuscaDadosReceita );
			if ($oTabRec->numrows == 0) {
				throw new Exception ( "Não foram encontrados dados para a receita {$oDadosTxt->receita}" );
			}
			$oDadosReceita = db_utils::fieldsMemory ( $rsDadosReceita, 0 );
			
			if (! isset ( $aConfiguracao ["conta_instituicao{$oDadosReceita->instituicao}"] ) or $aConfiguracao ["conta_instituicao{$oDadosReceita->instituicao}"] == 0 or $aConfiguracao ["conta_instituicao{$oDadosReceita->instituicao}"] == "") {
				throw new Exception ( "verifique configuracao da conta para a instituição {$oDadosReceita->instituicao}" );
			}
			
			$iContaTesouraria = $aConfiguracao ["conta_instituicao" . $oDadosReceita->instituicao];
			
			$oDadosLinhaTxt = new stdClass ();
			$oDadosLinhaTxt->data_planilha = implode ( "-", array_reverse ( explode ( "/", $dDataGeracao ) ) );
			$oDadosLinhaTxt->caracteristica_peculiar = "000";
			$oDadosLinhaTxt->numcgm = $iNumCgm;
			$oDadosLinhaTxt->conta_tesouraria = $iContaTesouraria;
			$oDadosLinhaTxt->data_recebimento = implode ( "-", array_reverse ( explode ( "/", $oDadosTxt->data_arrecadacao ) ) );
			$oDadosLinhaTxt->observacao = "Arrecadacao Diária";
			$oDadosLinhaTxt->recurso = $oDadosReceita->recurso;
			$oDadosLinhaTxt->receita = $oDadosReceita->receita;
			$oDadosLinhaTxt->valor_arrecadacao = substr ( $oDadosTxt->valor_arrecadacao, 0, strlen ( $oDadosTxt->valor_arrecadacao ) - 2 ) . "." . substr ( $oDadosTxt->valor_arrecadacao, - 2 );
			
			$aDados [$oDadosReceita->instituicao] [] = $oDadosLinhaTxt;
		}
		
		$sHashInstituicao = "";
		$aInstituicoesGeradas = array ();
		$aPlanilhas = array ();
		
		if (count($aDados) == 0) {
			throw new Exception("Nenhum registro encontrado para processamento.\nVerifique as permissões, codificação e o conteudo do arquivo {$sArquivo}");
		}
		
		foreach ( $aDados as $iInstituicao => $oDados ) {
			
			foreach ( $oDados as $oRegistros ) {
				
				if ($sHashInstituicao != $iInstituicao) {
					
					if (! in_array ( $iInstituicao, $aInstituicoesGeradas ) && $sHashInstituicao != "") {
						$oPlanilhaArrecadacao->salvar ();
						$aInstituicoesGeradas [] = $iInstituicao;
						$aPlanilhasGeradas [$sHashInstituicao] = $oPlanilhaArrecadacao->getCodigo ();
					}
					
					$oPlanilhaArrecadacao = new PlanilhaArrecadacao ();
					$oPlanilhaArrecadacao->setDataCriacao ( $oRegistros->data_planilha );
					$oPlanilhaArrecadacao->setInstituicao ( InstituicaoRepository::getInstituicaoByCodigo ( $iInstituicao ) );
					$oPlanilhaArrecadacao->setProcessoAdministrativo ( null );
					
					$sHashInstituicao = $iInstituicao;
				}
				
				$oReceitaPlanilha = new ReceitaPlanilha ();
				$oReceitaPlanilha->setCaracteristicaPeculiar ( new CaracteristicaPeculiar ( $oRegistros->caracteristica_peculiar ) );
				$oReceitaPlanilha->setCGM ( CgmFactory::getInstanceByCgm ( $oRegistros->numcgm ) );
				$oReceitaPlanilha->setContaTesouraria ( new contaTesouraria ( $oRegistros->conta_tesouraria ) );
				$oReceitaPlanilha->setDataRecebimento ( new DBDate ( $oRegistros->data_recebimento ) );
				$oReceitaPlanilha->setInscricao ( null );
				$oReceitaPlanilha->setMatricula ( null );
				$oReceitaPlanilha->setObservacao ( $oRegistros->observacao );
				$oReceitaPlanilha->setOperacaoBancaria ( null );
				$oReceitaPlanilha->setOrigem ( 1 );
				$oReceitaPlanilha->setRecurso ( new Recurso ( $oRegistros->recurso ) );
				$oReceitaPlanilha->setTipoReceita ( $oRegistros->receita );
				$oReceitaPlanilha->setValor ( $oRegistros->valor_arrecadacao );
				
				$oPlanilhaArrecadacao->adicionarReceitaPlanilha ( $oReceitaPlanilha );
			}
		}
		
		$oPlanilhaArrecadacao->salvar ();
		$aInstituicoesGeradas [] = $iInstituicao;
		$aPlanilhasGeradas [$sHashInstituicao] = $oPlanilhaArrecadacao->getCodigo ();
		
		$sMensagemRetorno = "Operação realizada com sucesso!\n";
		$sMensagemRetorno .= "Planilhas geradas:\n";
		foreach ( $aPlanilhasGeradas as $iInstituicao => $iPlanilha ) {
			$sMensagemRetorno .= "Instituicao: {$iInstituicao} - Planilha: {$iPlanilha}\n";
			
			$oArquivoRetornoArrecadacaoTributariaPlanilhas->sequencial = null;
			$oArquivoRetornoArrecadacaoTributariaPlanilhas->arquivoretornoarrecadacaotributaria = $iSequencialArquivoRetorno;
			$oArquivoRetornoArrecadacaoTributariaPlanilhas->placaixa = $iPlanilha;
			$oArquivoRetornoArrecadacaoTributariaPlanilhas->incluir ( null );
			if ($oArquivoRetornoArrecadacaoTributariaPlanilhas->erro_status == "0") {
				throw new Exception ( "Erro incluindo dados das planilhas geradas na baixa do arquivo.\nErro:" . $oArquivoRetornoArrecadacaoTributariaPlanilhas->erro_msg );
			}
		}
		
		db_msgbox ( $sMensagemRetorno );
		
		db_fim_transacao ( false );
	} catch ( Exception $oException ) {
		
		db_fim_transacao ( true );
		db_msgbox ( "{$oException->getMessage()}" );
	}
}
?>
<html>
<head>
<title>DBSeller Inform&aacute;tica Ltda - P&aacute;gina Inicial</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires" CONTENT="0">
<?
db_app::load ( "scripts.js, strings.js, prototype.js, estilos.css" );
?>
</head>

<body class="body-default" onLoad="a=1">
<?
/*
 * Caso nao seja configurado o local para os arquivos,
 * mostramos o formulário default
 */
if ($sDiretorioArquivos == "") {
	
	echo '<form name="form1" enctype="multipart/form-data" onsubmit=" return js_verifica()" method="post" action="">';
	echo '<fieldset style="margin: 40px auto 10px; width: 700px;">';
	echo '	<legend>';
	echo '		<strong>Processa arquivo arrecadações tributárias</strong>';
	echo '	</legend>';
	echo '	<table align="center">';
	echo '		<tr>';
	echo '			<td nowrap><b>Arquivo: </b></td>';
	echo '			<td><input name="arquivo" type="file"></td>';
	echo '		</tr>';
	echo '	</table>';
	echo '</fieldset>';
	echo '<center>';
	echo '	<input name="processar" type="submit" id="processar" value="Processar">';
	echo '</center>';
	echo '</form>';
	
} else {
	
	$sBgColor = "#FFFFFF";
	
	echo '<form name="form1" method="post" action="">';
	echo '<fieldset style="margin: 40px auto 10px; width: 700px;">';
	echo '  <legend>';
	echo '   <strong>Processa arquivo arrecadações tributárias</strong>';
	echo '  </legend>';
	echo '  <table align="center" width="80%" cellpadding=3 cellspacing=0>';
	
	$oDiretorioArquivos = @dir ( $sDiretorioArquivos ) or die("Erro lendo diretório {$sDiretorioArquivos}.<br>Verifique o caminho ou as permissões de acesso.");
	echo "Arquivos pendentes no diretório '<strong>{$sDiretorioArquivos}</strong>':<br>";
	while ( $sArquivo = $oDiretorioArquivos->read () ) {
		
		$aArquivo = pathinfo ( $sDiretorioArquivos . $sArquivo );
		if (! in_array ( $aArquivo['extension'], $aExtensoesValidas )) {
			continue;
		}
		
		$sMd5Arquivo = md5 ( file_get_contents ( $sDiretorioArquivos . $sArquivo ) );
		
		$sSqlArquivoImportado = $oArquivoRetornoArrecadacaoTributaria->sql_query_file ( null, 'true', null, "md5 = '$sMd5Arquivo'" );
		$rsArquivoImportado = $oArquivoRetornoArrecadacaoTributaria->sql_record ( $sSqlArquivoImportado );
		if ($oArquivoRetornoArrecadacaoTributaria->numrows > 0) {
			continue;
		}
		
		$sBgColor = ($sBgColor == "#FFFFFF" ? "#C0C0C0" : "#FFFFFF");
		echo "<tr bgcolor='$sBgColor'>";
		echo "  <td width='80%'> {$sArquivo} </td>";
		echo "  <td width='20%' align='center'> ";
		echo "    <input type='submit' name='" . $aArquivo ["filename"] . "' value='Processar'>";
		echo "  </td>";
		echo "</tr>";
	}
	$oDiretorioArquivos->close ();
	
	echo "    <input type='hidden' name='processar' value=''>";
	echo '  </table>';
	echo '</fieldset>';
	echo '</form>';
}

db_menu ( db_getsession ( "DB_id_usuario" ), db_getsession ( "DB_modulo" ), db_getsession ( "DB_anousu" ), db_getsession ( "DB_instit" ) );
?>
</body>
</html>
<script type="text/javascript">
function js_verifica(){

  if( $F('arquivo') == "" ){

    alert( "Arquivo de retorno não informado!" );
    $('arquivo').focus();
    return false;
  }
  
}
</script>