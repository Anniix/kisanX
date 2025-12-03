let allProducts = [];
const perPage = 8;
let currentPage = 1;

// ✅ unified fetch
async function fetchProducts(){
  try {
    const res = await fetch('php/get_products.php'); // ✅ single endpoint
    allProducts = await res.json();
    renderPage(1);
  } catch (err) {
    console.error("Error fetching products:", err);
  }
}

function renderPage(page){
  currentPage = page;
  const start = (page-1)*perPage;
  const pageItems = allProducts.slice(start, start+perPage);
  const grid = document.getElementById('productsGrid');
  grid.innerHTML = '';

  if(pageItems.length === 0){
    grid.innerHTML = "<p>No products available.</p>";
    return;
  }

  pageItems.forEach(p=>{
    const card = document.createElement('div');
    card.className='card';
    card.innerHTML = `
      <img src="images/${p.image}" alt="${p.name}">  <!-- ✅ corrected path -->
      <h4>${p.name}</h4>
      <p>${p.description}</p>
      ${p.discount_percent>0?
        `<p class="price"><span class="old">₹${p.price}</span> 
         ₹${(p.price*(1-p.discount_percent/100)).toFixed(2)} 
         <span class="badge">-${p.discount_percent}%</span></p>`
        :<p class="price">₹${p.price}</p>}
      <button class="add-to-cart" data-id="${p.id}">Add to cart</button>
    `;
    grid.appendChild(card);
  });

  renderPagination();
}

function renderPagination(){
  const totalPages = Math.ceil(allProducts.length / perPage) || 1;
  const pag = document.getElementById('pagination');
  pag.innerHTML = '';
  for(let i=1;i<=totalPages;i++){
    const btn = document.createElement('button');
    btn.textContent = i;
    if(i===currentPage) btn.classList.add('active');
    btn.addEventListener('click', ()=> renderPage(i));
    pag.appendChild(btn);
  }
}

document.addEventListener("DOMContentLoaded", fetchProducts);