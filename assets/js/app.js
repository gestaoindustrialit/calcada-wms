document.addEventListener('DOMContentLoaded',()=>{
  const enhanceSearchableSelect=select=>{
    if(select.dataset.searchBound)return;
    select.dataset.searchBound='1';
    const wrapper=document.createElement('div');
    wrapper.className='searchable-select-wrap';
    const input=document.createElement('input');
    input.type='search';
    input.className='form-control searchable-select-input';
    input.placeholder=select.dataset.searchPlaceholder||'Pesquisar';
    input.autocomplete='off';
    const list=document.createElement('div');
    list.className='searchable-select-list';
    const selected=select.options[select.selectedIndex];
    input.value=selected?selected.text:'';
    select.classList.add('searchable-select-native');
    select.parentNode.insertBefore(wrapper,select);
    wrapper.appendChild(select);
    wrapper.appendChild(input);
    wrapper.appendChild(list);
    const options=()=>[...select.options].map(option=>({value:option.value,text:option.text,disabled:option.disabled}));
    const close=()=>list.classList.remove('is-open');
    const choose=option=>{select.value=option.value;input.value=option.text;select.dispatchEvent(new Event('change',{bubbles:true}));close();};
    const render=()=>{
      const term=input.value.toLowerCase().trim();
      const matches=options().filter(option=>!option.disabled&&option.text.toLowerCase().includes(term)).slice(0,30);
      list.innerHTML='';
      matches.forEach(option=>{
        const button=document.createElement('button');
        button.type='button';
        button.className='searchable-select-option';
        button.textContent=option.text;
        button.addEventListener('mousedown',event=>{event.preventDefault();choose(option);});
        list.appendChild(button);
      });
      list.classList.toggle('is-open',matches.length>0&&document.activeElement===input);
    };
    input.addEventListener('focus',render);
    input.addEventListener('input',render);
    input.addEventListener('keydown',event=>{
      if(event.key==='Enter'){
        const first=list.querySelector('.searchable-select-option');
        if(first){event.preventDefault();first.dispatchEvent(new MouseEvent('mousedown',{bubbles:true}));}
      }
      if(event.key==='Escape')close();
    });
    input.addEventListener('blur',()=>setTimeout(()=>{const current=select.options[select.selectedIndex];input.value=current?current.text:'';close();},120));
  };
  const bindSearchableSelect=enhanceSearchableSelect;
  document.querySelectorAll('[data-smart-form]').forEach(form=>form.addEventListener('submit',()=>{const btn=form.querySelector('button[type="submit"],button:not([type])');if(btn){btn.dataset.original=btn.textContent;btn.textContent='A guardar…';btn.disabled=true;}}));
  document.querySelectorAll('.searchable-select').forEach(bindSearchableSelect);
  document.querySelectorAll('[data-request-form]').forEach(form=>{
    const lines=form.querySelector('[data-request-lines]');
    const addButton=form.querySelector('[data-add-request-line]');
    if(!lines||!addButton)return;
    const refresh=()=>{
      const rows=[...lines.querySelectorAll('[data-request-line]')];
      rows.forEach((row,index)=>{
        row.querySelectorAll('[name^="items["]').forEach(input=>{input.name=input.name.replace(/items\[\d+\]/,`items[${index}]`);});
        const remove=row.querySelector('[data-remove-request-line]');
        if(remove)remove.disabled=rows.length===1;
      });
    };
    addButton.addEventListener('click',()=>{
      const source=lines.querySelector('[data-request-line]');
      if(!source)return;
      const clone=source.cloneNode(true);
      clone.querySelectorAll('.searchable-select-wrap').forEach(wrapper=>{const select=wrapper.querySelector('select');if(select)wrapper.replaceWith(select);});
      clone.querySelectorAll('input').forEach(input=>{input.value='';});
      clone.querySelectorAll('select').forEach(select=>{select.selectedIndex=0;delete select.dataset.searchBound;bindSearchableSelect(select);});
      lines.appendChild(clone);
      refresh();
    });
    lines.addEventListener('click',event=>{
      const remove=event.target.closest('[data-remove-request-line]');
      if(!remove)return;
      const row=remove.closest('[data-request-line]');
      if(row&&lines.querySelectorAll('[data-request-line]').length>1){row.remove();refresh();}
    });
    refresh();
  });

  document.querySelectorAll('[data-inventory-movement-form]').forEach(form=>{
    let stock=[];
    try{stock=JSON.parse(form.dataset.inventoryLocations||'[]');}catch(e){stock=[];}
    const item=form.querySelector('[name="item_id"]');
    const warehouse=form.querySelector('[name="warehouse_id"]');
    const movement=form.querySelector('[name="movement_type"]');
    const location=form.querySelector('[name="location"]');
    const sourceLocation=form.querySelector('[name="source_location"]');
    const quantity=form.querySelector('[name="quantity"]');
    const minQuantity=form.querySelector('[name="min_quantity"]');
    const syncSearchableInput=select=>{
      const wrapper=select?.closest('.searchable-select-wrap');
      const input=wrapper?.querySelector('.searchable-select-input');
      const selected=select?.options[select.selectedIndex];
      if(input&&selected)input.value=selected.text;
    };
    const stockForItem=()=>stock.find(row=>String(row.item_id)===String(item?.value||'')&&row.location)||null;
    const currentStock=()=>stock.find(row=>String(row.item_id)===String(item?.value||'')&&String(row.warehouse_id)===String(warehouse?.value||'')&&row.location)||null;
    const isSplit=()=>movement&&movement.value==='split';
    const applyStockRow=row=>{
      if(!row)return;
      if(sourceLocation)sourceLocation.value=row.location||'';
      if(!isSplit()&&location)location.value=row.location||'';
      if(minQuantity&&!minQuantity.value)minQuantity.value=row.min_quantity??'';
    };
    const fillDefaultLocation=()=>applyStockRow(currentStock());
    const fillDefaultArticleLocation=()=>{
      const row=stockForItem();
      if(row&&warehouse&&String(warehouse.value)!==String(row.warehouse_id)){
        warehouse.value=String(row.warehouse_id);
        syncSearchableInput(warehouse);
      }
      applyStockRow(row||currentStock());
    };
    const refreshMovement=()=>{
      if(quantity)quantity.placeholder=isSplit()?'Qtd a dividir':(movement&&movement.value==='out'?'Quantidade a sair':'Quantidade');
      if(location){
        location.placeholder=isSplit()?'Localização de destino':'Localização';
        location.title=isSplit()?'Localização de destino para a quantidade dividida':'Localização do movimento';
        if(isSplit())location.value='';
      }
      fillDefaultLocation();
    };
    item&&item.addEventListener('change',fillDefaultArticleLocation);
    warehouse&&warehouse.addEventListener('change',fillDefaultLocation);
    movement&&movement.addEventListener('change',refreshMovement);
    fillDefaultArticleLocation();
    refreshMovement();
  });

  const requests=document.getElementById('requestsChart');
  if(requests&&window.chartPayload)new Chart(requests,{type:'bar',data:{labels:window.chartPayload.labels,datasets:window.chartPayload.datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'€ '+v}}}}});
  const spend=document.getElementById('articleSpendChart');
  if(spend&&window.articleSpend)new Chart(spend,{type:'bar',data:{labels:window.articleSpend.labels,datasets:[{label:'Gasto',data:window.articleSpend.data,backgroundColor:['#1062fe','#12b76a','#f79009','#7a5af8','#f04438','#06aed4']}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{callback:v=>'€ '+v}}}}});
});
