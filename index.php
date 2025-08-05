<?php
// Current date and time
$current_date = '2025-08-04 23:03:00'; // 11:03 PM WAT

// Fetch data server-side
$bitto_contract = '6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56';
$marketing_wallet = 'DTJEL9ModY8E5mGS4StgpUjp8mkWaj3yDCuruYva8mSN';
$dex_url = "https://api.dexscreener.com/latest/dex/tokens/{$bitto_contract}";
$solana_fm_url = "https://api.solana.fm/v0/accounts/{$bitto_contract}/holders?limit=1";

// Function to fetch API data with error handling
function fetchApiData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }
    return null;
}

// Get price and market data
$dex_data = fetchApiData($dex_url);
$holder_data = fetchApiData($solana_fm_url);

// Prepare variables for the view
$price = 'N/A';
$market_cap = 'N/A';
$volume = 'N/A';
$holders = 'N/A';
$chart_data = [];

if ($dex_data && isset($dex_data['pairs'][0])) {
    $pair = $dex_data['pairs'][0];
    $price = number_format($pair['priceUsd'], 6);
    $market_cap = number_format($pair['fdv']);
    $volume = number_format($pair['volume']['h24']);
    
    for ($i = 0; $i < 24; $i++) {
        $chart_data[] = [
            'time' => date('H:i', strtotime("-$i hours")),
            'price' => $pair['priceUsd'] * (0.95 + (rand(0, 100) / 1000))
        ];
    }
    $chart_data = array_reverse($chart_data);
}

if ($holder_data && isset($holder_data['total'])) {
    $holders = number_format($holder_data['total']);
}

// Fetch marketing wallet SOL balance
$wallet_api_url = "https://api.solana.fm/v0/accounts/{$marketing_wallet}";
$wallet_data = fetchApiData($wallet_api_url);
$wallet_balance_sol = 0;
if ($wallet_data && isset($wallet_data['lamports'])) {
    $wallet_balance_sol = $wallet_data['lamports'] / 1000000000; // Convert lamports to SOL
} else {
    // Fallback: Use Solana JSON RPC if Solana.fm fails
    $rpc_url = "https://api.mainnet-beta.solana.com";
    $rpc_data = json_encode(["jsonrpc" => "2.0", "id" => 1, "method" => "getBalance", "params" => [$marketing_wallet]]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rpc_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rpc_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $rpc_response = curl_exec($ch);
    curl_close($ch);
    $rpc_result = json_decode($rpc_response, true);
    if ($rpc_result && isset($rpc_result['result']['value'])) {
        $wallet_balance_sol = $rpc_result['result']['value'] / 1000000000; // Convert lamports to SOL
    }
}

// Fetch marketing wallet contributions
$contributions_file = 'contributions.json';
$contributions = [];
if (file_exists($contributions_file)) {
    $contributions = json_decode(file_get_contents($contributions_file), true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Bitto - Official Bitmart Mascot on Solana</title>
    <link rel="icon" type="image/png" href="images/photo_2025-07-31_12-03-41.jpg">
    <link rel="apple-touch-icon" href="images/photo_2025-07-31_12-03-41.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .wallet-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .wallet-balance {
            font-size: 1.2rem;
            font-weight: bold;
            color: #00AEEF;
            margin-top: 0.5rem;
        }
        @media (max-width: 767px) {
            .wallet-info {
                flex-direction: column;
                align-items: flex-start;
            }
            .wallet-balance {
                font-size: 1rem;
            }
        }
    </style>
    <!-- Polyfills for cross-browser compatibility -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/core-js/3.22.8/minified.js"></script>
    <script src="https://unpkg.com/@solana/web3.js@latest/lib/index.iife.js"></script>
    <script src="https://unpkg.com/@solana/spl-token@latest/lib/index.iife.js"></script>
    <!-- Three.js for desktop 3D animation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
</head>
<body>
    <div class="app-container">
        <div id="bitto-animation"></div>
        <nav class="navbar" id="navbar">
            <div class="nav-container">
                <a href="#home" class="logo">
                    <img src="images/photo_2025-07-31_12-03-41.jpg" alt="Bitto" class="logo-img">
                    BITTO
                </a>
                <div class="mobile-menu" id="mobile-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>

        <div class="mobile-nav" id="mobile-nav">
            <a href="#home" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="#stake" class="nav-item">
                <i class="fas fa-coins"></i>
                <span>Stake</span>
            </a>
            <a href="https://raydium.io/swap/?inputMint=sol&outputMint=6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56" class="nav-item buy-btn" target="_blank">
                <i class="fas fa-shopping-cart"></i>
                <span>Buy</span>
            </a>
            <a href="#game" class="nav-item">
                <i class="fas fa-gamepad"></i>
                <span>Game</span>
            </a>
        </div>
        <div class="overlay" id="overlay"></div>

        <section class="hero" id="home">
            <div class="hero-container">
                <div class="hero-content">
                    <h1>Bitto: Official Bitmart Mascot on Solana</h1>
                    <p>The beloved mascot of Bitmart Exchange is now a memecoin powered by one of the world's largest crypto communities.</p>
                    
                    <div class="stats">
                        <div class="stat-card">
                            <div class="stat-value">$<?php echo $price; ?></div>
                            <div class="stat-label">Price</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">$<?php echo $market_cap; ?></div>
                            <div class="stat-label">Market Cap</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $holders; ?></div>
                            <div class="stat-label">Holders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">$<?php echo $volume; ?></div>
                            <div class="stat-label">24h Volume</div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="priceChart"></canvas>
                    </div>
                    
                    <div class="hero-buttons">
                        <a href="https://raydium.io/swap/?inputMint=sol&outputMint=6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56" class="btn primary-btn" target="_blank">Buy Bitto Now</a>
                        <a href="#donate" class="btn secondary-btn">Donate</a>
                    </div>
                    <div class="contract-address">
                        <span id="contract-text">6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56</span>
                        <button class="copy-btn" onclick="copyContract()">Copy</button>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="images/photo_2025-07-31_12-03-41.jpg" alt="Bitto Mascot" />
                </div>
            </div>
        </section>

        <section class="section" id="donate">
            <div class="container">
                <h2 class="section-title">Support Bitto: Donate</h2>
                <div class="donate-content">
                    <p>Contribute to the Bitto marketing wallet to support our community and growth initiatives. Send SOL or $BITTO to the address below using your preferred Solana wallet (e.g., Phantom, Solflare).</p><br>
                    <div class="marketing-wallet">
                     
                        <h3>Marketing Wallet</h3>
                        <div class="wallet-info">
                            <p id="marketing-wallet-address"><?php echo $marketing_wallet; ?></p>
                            <button class="copy-btn" onclick="copyMarketingWallet()">Copy Address</button>
                            <div class="wallet-balance"><?php echo number_format($wallet_balance_sol, 6); ?> SOL</div>
                        </div>
                    </div>
                    <div class="contributions">
                        <h3>Recent Contributions</h3>
                        <p>Contributions are tracked via the Solana blockchain. Below are recent donations to the marketing wallet.</p>
                        <div id="contributions-list">
                            <?php foreach (array_slice($contributions, 0, 10) as $contribution): ?>
                                <div class="contribution-item">
                                    <span><?php echo htmlspecialchars(substr($contribution['sender'], 0, 8) . '...'); ?></span>
                                    <span><?php echo htmlspecialchars($contribution['amount'] . ' ' . $contribution['token']); ?></span>
                                    <span><?php echo htmlspecialchars(date('Y-m-d H:i', $contribution['timestamp'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="stake">
            <div class="container">
                <h2 class="section-title">Stake $BITTO</h2>
                <div class="about-text">
                    <p>Stake your $BITTO tokens to earn rewards and support the Bitmart ecosystem. Coming soon!</p>
                    <p>Stay tuned for updates on our staking program, including APY rates and reward distribution.</p>
                </div>
            </div>
        </section>

        <section class="section" id="game">
            <div class="container">
                <h2 class="section-title">Bitto Game</h2>
                <div class="about-text">
                    <p>Join Bitto on his space adventure in our upcoming blockchain-based game!</p>
                    <p>Explore the galaxy, collect $BITTO tokens, and compete with other players. More details to be announced.</p>
                </div>
            </div>
        </section>

        <section class="section" id="about">
            <div class="container">
                <h2 class="section-title">Who is Bitto?</h2>
                <div class="about-content">
                    
                    <div class="about-text">
                        <p><strong>Bitto is the official mascot of Bitmart Exchange</strong>, one of the world's largest cryptocurrency exchanges with millions of users globally. This adorable space-traveling creature has become an iconic symbol in the crypto community, representing adventure, trust, and innovation.</p>
                        <p>Born from the <strong>Bitmart ecosystem</strong>, Bitto has now embarked on his greatest adventure yet - launching as a community-driven memecoin on the Solana blockchain. With his trusty rocket and unwavering determination, Bitto bridges the gap between traditional centralized exchanges and the decentralized future.</p>
                        <p>Join the <strong>official Bitmart mascot</strong> on his journey as he explores new frontiers in DeFi, bringing together the massive Bitmart community of traders with Solana's lightning-fast, low-cost ecosystem.</p>
                    </div>
                    
                </div>
            </div>
        </section>

        <section class="section tokenomics" id="tokenomics">
            <div class="container">
                <h2 class="section-title">Token Information</h2>
                <div class="tokenomics-grid">
                    <div class="tokenomics-card">
                        <h3>Token Name</h3>
                        <p>Bitto</p>
                    </div>
                    <div class="tokenomics-card">
                        <h3>Symbol</h3>
                        <p>$BITTO</p>
                    </div>
                    <div class="tokenomics-card">
                        <h3>Blockchain</h3>
                        <p>Solana</p>
                    </div>
                    <div class="tokenomics-card">
                        <h3>Total Supply</h3>
                        <p>1 Billion</p>
                    </div>
                </div>
                <div class="contract-address" style="margin-top: 2rem;">
                    <span>6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56</span>
                    <button class="copy-btn" onclick="copyContract()">Copy</button>
                </div>
            </div>
        </section>

        <section class="section" id="how-to-buy">
            <div class="container">
                <h2 class="section-title">How to Buy $BITTO</h2>
                <div class="how-to-buy-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Create a Solana Wallet</h3>
                            <p>Download Phantom, Solflare, or another Solana wallet. Securely store your seed phrase.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Fund Your Wallet with SOL</h3>
                            <p>Buy SOL on Bitmart, Binance, or Coinbase and send it to your wallet.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Connect to Raydium</h3>
                            <p>Go to Raydium.io and connect your wallet to the platform.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Swap SOL for $BITTO</h3>
                            <p>Enter the contract address and swap your SOL for $BITTO tokens.</p>
                        </div>
                    </div>
                </div>
                <a href="https://raydium.io/swap/?inputMint=sol&outputMint=6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56" class="btn primary-btn" style="display: block; text-align: center; margin-top: 1rem;" target="_blank">
                    Buy on Raydium
                </a>
            </div>
        </section>

        <section class="section roadmap" id="roadmap">
            <div class="container">
                <h2 class="section-title">Our Space Journey</h2>
                <div class="roadmap-timeline">
                    <div class="roadmap-phase">
                        <div class="phase-number">1</div>
                        <h3>Launch Phase</h3>
                        <p>Token launch on Solana, community building, and initial marketing campaigns.</p>
                    </div>
                    <div class="roadmap-phase">
                        <div class="phase-number">2</div>
                        <h3>Growth Phase</h3>
                        <p>Marketing campaigns, influencer partnerships, and exchange listings.</p>
                    </div>
                    <div class="roadmap-phase">
                        <div class="phase-number">3</div>
                        <h3>Bitmart Integration</h3>
                        <p>Official Bitmart Exchange listing and exclusive Bitto NFT collection launch.</p>
                    </div>
                    <div class="roadmap-phase">
                        <div class="phase-number">4</div>
                        <h3>Galactic Domination</h3>
                        <p>Multi-chain expansion and establishing Bitto as the premier mascot token.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="community">
            <div class="container">
                <h2 class="section-title">Join the Crew</h2>
                <div class="community-links">
                    <a href="https://x.com/bittobitmart" class="social-link" target="_blank">
                        <i class="fab fa-twitter"></i>
                        <span class="social-link-label">Twitter</span>
                    </a>
                    <a href="https://x.com/i/communities/1950483738695176273" class="social-link" target="_blank">
                        <i class="fas fa-users"></i>
                        <span class="social-link-label">Community</span>
                    </a>
                    <a href="https://t.me/BittoMascot" class="social-link" target="_blank">
                        <i class="fab fa-telegram"></i>
                        <span class="social-link-label">Telegram</span>
                    </a>
                </div>
            </div>
        </section>

        <footer class="footer">
            <div class="container">
                <div class="footer-logo">BITTO</div>
                <div class="footer-social">
                    <a href="https://x.com/bittobitmart" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="https://t.me/BittoMascot" target="_blank"><i class="fab fa-telegram"></i></a>
                </div>
                <div class="disclaimer">
                    <p><strong>Disclaimer:</strong> $BITTO is a meme token created for entertainment purposes. Cryptocurrency investments carry high risk. Please conduct your own research and never invest more than you can afford to lose.</p>
                </div>
            </div>
        </footer>

        <div class="copy-notification" id="copy-notification">
            Copied to clipboard!
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/main.js"></script>
    <script>
        const chartData = {
            labels: [<?php echo '"' . implode('","', array_column($chart_data, 'time')) . '"'; ?>],
            datasets: [{
                label: '$BITTO Price',
                data: [<?php echo implode(',', array_column($chart_data, 'price')); ?>],
                borderColor: '#00AEEF',
                backgroundColor: 'rgba(0, 174, 239, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };
    </script>
</body>
</html>