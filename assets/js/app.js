document.addEventListener('DOMContentLoaded',()=>{
  const bindSearchableSelect=select=>{if(select.dataset.searchBound)return;select.dataset.searchBound='1';select.addEventListener('keydown',e=>{const key=e.key.toLowerCase();if(key.length!==1)return;const opt=[...select.options].find(o=>o.text.toLowerCase().includes(key));if(opt)select.value=opt.value;});};
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
  const requests=document.getElementById('requestsChart');
  if(requests&&window.chartPayload)new Chart(requests,{type:'bar',data:{labels:window.chartPayload.labels,datasets:window.chartPayload.datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'€ '+v}}}}});
  const spend=document.getElementById('articleSpendChart');
  if(spend&&window.articleSpend)new Chart(spend,{type:'bar',data:{labels:window.articleSpend.labels,datasets:[{label:'Gasto',data:window.articleSpend.data,backgroundColor:['#1062fe','#12b76a','#f79009','#7a5af8','#f04438','#06aed4']}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{callback:v=>'€ '+v}}}}});
});
