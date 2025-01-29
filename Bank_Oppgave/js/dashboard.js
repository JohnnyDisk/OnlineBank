document.addEventListener('DOMContentLoaded', function() {
    // Fetch account balance
    function updateBalance() {
        fetch('api/get_balance.php')
            .then(response => response.json())
            .then(data => {
                document.querySelector('.balance').textContent = 
                    `$${parseFloat(data.balance).toFixed(2)}`;
            })
            .catch(error => console.error('Error:', error));
    }

    // Fetch recent transactions
    function loadRecentTransactions() {
        fetch('api/get_recent_transactions.php')
            .then(response => response.json())
            .then(data => {
                const transactionList = document.querySelector('.transaction-list');
                transactionList.innerHTML = '';
                
                data.forEach(transaction => {
                    const transactionElement = document.createElement('div');
                    transactionElement.className = 'transaction-item';
                    transactionElement.innerHTML = `
                        <div class="transaction-date">${transaction.date}</div>
                        <div class="transaction-description">${transaction.description}</div>
                        <div class="transaction-amount ${transaction.type}">${transaction.amount}</div>
                    `;
                    transactionList.appendChild(transactionElement);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    // Update data every 30 seconds
    updateBalance();
    loadRecentTransactions();
    setInterval(() => {
        updateBalance();
        loadRecentTransactions();
    }, 30000);
});

// Mobile menu toggle
const mobileMenuButton = document.createElement('button');
mobileMenuButton.className = 'mobile-menu-button';
mobileMenuButton.innerHTML = '☰';
document.querySelector('.navbar').appendChild(mobileMenuButton);

mobileMenuButton.addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('show');
}); 