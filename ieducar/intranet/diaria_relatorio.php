<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itaja�								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software P�blico Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itaja�			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  �  software livre, voc� pode redistribu�-lo e/ou	 *
	*	modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a vers�o 2 da	 *
	*	Licen�a   como  (a  seu  crit�rio)  qualquer  vers�o  mais  nova.	 *
	*																		 *
	*	Este programa  � distribu�do na expectativa de ser �til, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia impl�cita de COMERCIALI-	 *
	*	ZA��O  ou  de ADEQUA��O A QUALQUER PROP�SITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licen�a  P�blica  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Voc�  deve  ter  recebido uma c�pia da Licen�a P�blica Geral GNU	 *
	*	junto  com  este  programa. Se n�o, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsCadastro.inc.php");
require_once ("include/relatorio.inc.php");
require_once ("include/Geral.inc.php");


class clsIndex extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} Relat�rio de Di�rias" );
		$this->processoAp = "336";
	}
}

class indice extends clsCadastro
{
	var $cod_funcionario;
	var $nome_funcionario;
	var $data_inicial;
	var $data_final;
	var $data_partida;
	var $data_chegada;
	var $valor_total;

	function Inicializar()
	{
		@session_start();
		$this->cod_pessoa_fj = $_SESSION['id_pessoa'];
		session_write_close();
		$retorno = "Novo";
		return $retorno;
	}

	function Gerar()
	{
		$db = new clsBanco();
		$db->Consulta( "SELECT a.ref_funcionario FROM pmidrh.diaria a WHERE ativo = 't'" );
		$ids = array();
		while ( $db->ProximoRegistro() ) {
			list( $cod ) = $db->Tupla();
			$ids[$cod] = $cod;
		}

		if ($ids && count($ids))
		{
			$objPessoa = new clsPessoa_();
			$pessoas = $objPessoa->lista( false, false, false, false, $ids );

			$lista = array();
			$lista["0"]="Escolha um Funcion�rio...";
			foreach ( $pessoas AS $pessoa )
			{
				$lista[$pessoa["idpes"]] = $pessoa["nome"];
			}

			$this->campoLista( "funcionario", "Funcion�rio", $lista, $this->funcionario);
			$this->campoData("data_inicial", "Data Inicial", $this->data_inicial);
			$this->campoData("data_final", "Data Final", $this->data_final);
		}
		else
		{
			$this->campoRotulo("aviso","Aviso","Nenhuma Di�ria cadastrada");
		}
	}

	function Novo()
	{
		$meses = array();
		$meses[1] = "Janeiro";
		$meses[2] = "Fevereiro";
		$meses[3] = "Mar�o";
		$meses[4] = "Abril";
		$meses[5] = "Maio";
		$meses[6] = "Junho";
		$meses[7] = "Julho";
		$meses[8] = "Agosto";
		$meses[9] = "Setembro";
		$meses[10] = "Outubro";
		$meses[11] = "Novembro";
		$meses[12] = "Dezembro";

		$mesAtual = "";

		if ( $this->funcionario != "0" )
		{
			if ($this->data_inicial != "" ||
				$this->data_final != "")
			{
				$AND = '';
				if ($this->data_inicial)
				{
					$data = explode("/", $this->data_inicial);
					$dia_i = $data[0];
					$mes_i = $data[1];
					$ano_i = $data[2];

					$data_inicial = $ano_i."/".$mes_i."/".$dia_i." 00:00:00";

					$AND = " AND data_pedido >= '{$data_inicial}'";
				}

				if ($this->data_final)
				{

					$data_ = explode("/", $this->data_final);
					$dia_f = $data_[0];
					$mes_f = $data_[1];
					$ano_f = $data_[2];

					$data_final = $ano_f."/".$mes_f."/".$dia_f." 23:59:59";

					$AND .= " AND data_pedido <= '{$data_final}'";
				}
			}

			$sql = "SELECT ref_funcionario, data_partida, data_chegada, COALESCE(vl100,1) + COALESCE(vl75,1) + COALESCE(vl50,1) + COALESCE(vl25,1) as valor, objetivo, destino FROM pmidrh.diaria WHERE ref_funcionario = {$this->funcionario} {$AND} AND ativo = 't' ORDER BY data_partida DESC";

			$db2 = new clsBanco();
			$nome = $db2->campoUnico("SELECT nome FROM cadastro.pessoa WHERE idpes = {$this->funcionario}");
			$nome_funcionario = $nome;

			$relatorio = new relatorios( "Relat�rio de Di�rias - {$nome}", 200, false, "SEGPOG - Departamento de Log�stica", "A4", "Prefeitura de Itaja�\nSEGPOG - Departamento de Log�stica\nRua Alberto Werner, 100 - Vila Oper�ria\nCEP. 88304-053 - Itaja� - SC");

			//tamanho do retangulo, tamanho das linhas.
			$relatorio->novaPagina(30,28);

			$db3 = new clsBanco();
			$db3->Consulta( $sql );
			if( $db3->Num_Linhas() )
			{
				while ( $db3->ProximoRegistro() )
				{
					list( $cod_funcionario, $data_partida, $data_chegada, $valor_total, $objetivo, $destino ) = $db3->Tupla();

					$mes = $meses[date( "n", strtotime( $data_partida ) )];
					if( $mes != $mesAtual )
					{
						if( $mesAtual != "" )
						{
							//$relatorio->novalinha( array( "" ), 1, 10 );
							$relatorio->novalinha( array( "" ), 1, 10, false, false, false, false, false, false, true );
						}
						$mesAtual = $mes;
						$relatorio->novalinha( array( $mesAtual ), 1, 13, true );
						$relatorio->novalinha( array( "Data Partida", "Data Chegada", "Valor Total" ), 0, 13, true);
					}

					$data_partida = date( "d/m/Y H:i", strtotime( substr($data_partida,0,19 ) ) );
					$data_chegada = date( "d/m/Y H:i", strtotime( substr($data_chegada,0,19 ) ) );

					$relatorio->novalinha( array( $data_partida, $data_chegada, number_format($valor_total, 2, ',', '.') ),1,13);
					$relatorio->novalinha( array( "Objetivo: " . $objetivo ),20,13);
					$relatorio->novalinha( array( "Destino: " . $destino ),20,13);
				}
				// pega o link e exibe ele ao usuario
				$link = $relatorio->fechaPdf();
				$this->campoRotulo("arquivo","Arquivo", "<a href='" . $link . "'>Visualizar Relat�rio</a>");
			}
			else
			{
				$this->campoRotulo("aviso","Aviso", "Nenhum Funcion�rio neste relatorio.");
			}
		}
		else
		{
			$this->campoRotulo("aviso","Aviso", "Escolha um Funcion�rio.");
		}

		$this->largura = "100%";
		return true;
	}

	function Editar()
	{
	}

	function Excluir()
	{
	}
}

$pagina = new clsIndex();

$miolo = new indice();
$pagina->addForm( $miolo );

$pagina->MakeAll();
?>