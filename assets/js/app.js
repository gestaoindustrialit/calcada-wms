document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-smart-form]').forEach(form=>form.addEventListener('submit',()=>{const btn=form.querySelector('button[type="submit"],button:not([type])');if(btn){btn.dataset.original=btn.textContent;btn.textContent='A guardar…';btn.disabled=true;}}));
  document.querySelectorAll('.searchable-select').forEach(select=>{select.addEventListener('keydown',e=>{const key=e.key.toLowerCase();if(key.length!==1)return;const opt=[...select.options].find(o=>o.text.toLowerCase().includes(key));if(opt)select.value=opt.value;});});
  const requests=document.getElementById('requestsChart');
  if(requests&&window.chartPayload)new Chart(requests,{type:'bar',data:{labels:window.chartPayload.labels,datasets:window.chartPayload.datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'€ '+v}}}}});
  const spend=document.getElementById('articleSpendChart');
  if(spend&&window.articleSpend)new Chart(spend,{type:'bar',data:{labels:window.articleSpend.labels,datasets:[{label:'Gasto',data:window.articleSpend.data,backgroundColor:['#1062fe','#12b76a','#f79009','#7a5af8','#f04438','#06aed4']}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{callback:v=>'€ '+v}}}}});
});
