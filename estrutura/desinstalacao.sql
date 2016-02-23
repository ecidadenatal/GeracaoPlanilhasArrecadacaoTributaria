drop table plugins.arquivoretornoarrecadacaotributaria;
drop sequence plugins.arquivoretornoarrecadacaotributaria_sequencial_seq;

drop table plugins.arquivoretornoarrecadacaotributariaplanilhas;
drop sequence plugins.arquivoretornoarrecadacaotributariaplanilhas_sequencial_seq;

delete from configuracoes.db_layoutcampos 
      using configuracoes.db_layoutlinha, configuracoes.db_layouttxt 
      where db51_codigo = db52_layoutlinha  
        and db50_codigo = db51_layouttxt 
        and db50_descr = 'PLUGIN ARQ. RET. ARREC. TRIBUT.';
        
delete from configuracoes.db_layoutlinha 
      using configuracoes.db_layouttxt 
      where db50_codigo = db51_layouttxt 
        and db50_descr = 'PLUGIN ARQ. RET. ARREC. TRIBUT.';  
        
delete from configuracoes.db_layouttxt 
      where db50_descr = 'PLUGIN ARQ. RET. ARREC. TRIBUT.';         
