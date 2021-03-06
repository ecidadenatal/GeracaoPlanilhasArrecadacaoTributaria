create table plugins.arquivoretornoarrecadacaotributaria (sequencial integer, 
                                                          arquivo text,
                                                          data date,
                                                          md5 varchar(50));
                                                          
create sequence plugins.arquivoretornoarrecadacaotributaria_sequencial_seq;

create table plugins.arquivoretornoarrecadacaotributariaplanilhas (sequencial integer, 
                                                                   arquivoretornoarrecadacaotributaria integer,
                                                                   placaixa integer);
                                                          
create sequence plugins.arquivoretornoarrecadacaotributariaplanilhas_sequencial_seq;

/*
insert into configuracoes.db_layouttxt (db50_codigo     , 
                          db50_descr      , 
                          db50_quantlinhas, 
                          db50_obs        , 
                          db50_layouttxtgrupo) 
                  values (nextval('configuracoes.db_layouttxt_db50_codigo_seq'), 
                          'PLUGIN ARQ. RET. ARREC. TRIBUT.', 
                          0, 
                          '', 
                          1);
                          
insert into configuracoes.db_layoutlinha (db51_codigo      ,
                            db51_layouttxt   ,
                            db51_descr       ,
                            db51_tipolinha   ,
                            db51_tamlinha    ,
                            db51_linhasantes ,
                            db51_linhasdepois,
                            db51_obs         ,
                            db51_separador   ,
                            db51_compacta)
                    values (nextval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                            currval('configuracoes.db_layouttxt_db50_codigo_seq'),
                            'HEADER',
                            1,
                            100,
                            0,
                            0,
                            '',
                            '',
                            'f');
                            
insert into configuracoes.db_layoutcampos (db52_codigo       ,
                             db52_layoutlinha  ,
                             db52_nome         ,
                             db52_descr        ,
                             db52_layoutformat ,
                             db52_posicao      ,
                             db52_default      ,
                             db52_tamanho      ,
                             db52_ident        ,
                             db52_imprimir     ,
                             db52_alinha       ,
                             db52_obs          ,
                             db52_quebraapos   )
                     values (nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                             currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                             'id_registro',
                             'IDENTIFICADOR DA LINHA',
                             1,
                             1,
                             0,
                             1,
                             't',
                             't',
                             'd',
                             'campo fixo',
                             0),
                            (nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                             currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                             'data_geracao',
                             'DATA DE GERA��O DO ARQUIVO',
                             1,
                             2,
                             null, 
                             10,
                             'f',
                             't',
                             'd',
                             '',
                             0), 
                            (nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                             currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                            'hora_geracao',
                            'HORA DE GERA��O DO ARQUIVO',
                            1,
                            12,
                            '',
                            8,
                            'f',
                            't',
                            'd',
                            '',
                            0), 
                           (nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                            currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                           'qtd_registros',
                           'QUANTIDADE DE REGISTROS DETALHE',
                           1,
                           20,
                           '',
                           10,
                           'f',
                           't',
                           'd',
                           '',
                           0);
                            

insert into configuracoes.db_layoutlinha (db51_codigo      ,
                            db51_layouttxt   ,
                            db51_descr       ,
                            db51_tipolinha   ,
                            db51_tamlinha    ,
                            db51_linhasantes ,
                            db51_linhasdepois,
                            db51_obs         ,
                            db51_separador   ,
                            db51_compacta)
                    values (nextval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                            currval('configuracoes.db_layouttxt_db50_codigo_seq'),
                            'REGISTROS',
                            3,
                            100,
                            0,
                            0,
                            '',
                            '',
                            'f');

insert into configuracoes.db_layoutcampos (db52_codigo       ,
                             db52_layoutlinha  ,
                             db52_nome         ,
                             db52_descr        ,
                             db52_layoutformat ,
                             db52_posicao      ,
                             db52_default      ,
                             db52_tamanho      ,
                             db52_ident        ,
                             db52_imprimir     ,
                             db52_alinha       ,
                             db52_obs          ,
                             db52_quebraapos   )
                     values ( nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'id_registro',
                              'IDENTIFICADOR DA LINHA',
                              1,
                              1,
                              1,
                              1,
                              't',
                              't',
                              'd',
                              'campo fixo',
                              0),
                            ( nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'receita',
                              'C�DIGO DA RECEITA',
                              1,
                              2,
                              null,
                              5,
                              'f',
                              't',
                              'd',
                              '',
                              0),
                            ( nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'data_arrecadacao',
                              'DATA DA ARRECADA��O DA RECEITA',
                              1,
                              7,
                              null,
                              10,
                              'f',
                              't',
                              'd',
                              '',
                              0),
                            ( nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'valor_arrecadacao',
                              'VALOR DA ARRECAD��O',
                              1,
                              17,
                              null,
                              12,
                              'f',
                              't',
                              'd',
                              '',
                              0),
                            ( nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'sequencial',
                              'SEQUENCIAL DO REGISTRO',
                              1,
                              29,
                              null,
                              10,
                              'f',
                              't',
                              'd',
                              '',
                              0);
      
                            
insert into configuracoes.db_layoutlinha (db51_codigo      ,
                            db51_layouttxt   ,
                            db51_descr       ,
                            db51_tipolinha   ,
                            db51_tamlinha    ,
                            db51_linhasantes ,
                            db51_linhasdepois,
                            db51_obs         ,
                            db51_separador   , 
                            db51_compacta)
                    values (nextval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                            currval('configuracoes.db_layouttxt_db50_codigo_seq'),
                            'TRAILLER',
                            5,
                            100,
                            0,
                            0,
                            '',
                            '',
                            'f');    
                            
insert into configuracoes.db_layoutcampos (db52_codigo       ,
                             db52_layoutlinha  ,
                             db52_nome         ,
                             db52_descr        ,
                             db52_layoutformat ,
                             db52_posicao      ,
                             db52_default      ,
                             db52_tamanho      ,
                             db52_ident        ,
                             db52_imprimir     ,
                             db52_alinha       ,
                             db52_obs          ,
                             db52_quebraapos   )
                      values (nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'id_registro',
                              'IDENTIFICADOR DA LINHA',
                              1,
                              1,
                              2,
                              1,
                              't',
                              't',
                              'd',
                              '',
                              0),
                             (nextval('configuracoes.db_layoutcampos_db52_codigo_seq'),
                              currval('configuracoes.db_layoutlinha_db51_codigo_seq'),
                              'valor_total_arquivo',
                              'VALOR TOTAL DO ARQUIVO',
                              1,
                              2,
                              '',
                              12,
                              'f',
                              't',
                              'd',
                              '',
                              0);*/
