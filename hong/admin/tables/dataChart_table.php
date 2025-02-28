<?php
if (!isset($data)) {
    die('Database connection not available');
}

// Get sales by category data
$category_sql = "SELECT 
    c.category_name,
    COUNT(DISTINCT o.order_id) as total_orders,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.price_at_purchase) as total_revenue
FROM categories c
LEFT JOIN products p ON c.category_id = p.category_id
LEFT JOIN order_items oi ON p.product_id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.order_id
WHERE o.status = 'completed'
GROUP BY c.category_id
ORDER BY total_revenue DESC";

$category_result = $data->query($category_sql);
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_data[] = $row;
}

// Get monthly revenue data for the past 12 months
$monthly_sql = "SELECT 
    DATE_FORMAT(o.order_date, '%Y-%m') as month,
    COUNT(DISTINCT o.order_id) as total_orders,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.price_at_purchase) as total_revenue
FROM orders o
JOIN order_items oi ON o.order_id = oi.order_id
WHERE o.status = 'completed'
AND o.order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY month
ORDER BY month ASC";

$monthly_result = $data->query($monthly_sql);
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Convert data to JSON for JavaScript
$category_json = json_encode($category_data);
$monthly_json = json_encode($monthly_data);
?>

<div class="charts-container">
    <div class="chart-row">
        <div class="chart-card">
            <h3>Sales by Category</h3>
            <div class="chart-type-selector" data-chart="category">
                <button data-type="pie" class="active">Pie</button>
                <button data-type="doughnut">Doughnut</button>
            </div>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>Monthly Revenue Trend</h3>
            <div class="chart-type-selector" data-chart="revenue">
                <button data-type="bar" class="active">Bar</button>
            </div>
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <div class="chart-row">
        <div class="chart-card full-width">
            <h3>Category Performance Comparison</h3>
            <div class="chart-type-selector" data-chart="comparison">
                <button data-type="bar" class="active">Bar</button>
                <button data-type="horizontal-bar">Horizontal Bar</button>
            </div>
            <canvas id="comparisonChart"></canvas>
        </div>
    </div>
</div>

<style>
    .charts-container {
        padding: 20px;
    }

    .chart-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .chart-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 40px;
        flex: 1;
        margin-bottom: 30px;
    }

    .chart-card.full-width {
        width: 100%;
    }

    .chart-card h3 {
        margin: 0 0 15px 0;
        color: #333;
    }

    canvas {
        width: 100% !important;
        height: 450px !important;
    }

    .chart-type-selector {
        margin-bottom: 15px;
    }

    .chart-type-selector button {
        background: #f0f0f0;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 10px;
    }

    .chart-type-selector button.active {
        background: #333;
        color: white;
    }

    .chart-type-selector button:hover {
        background: #ddd;
    }

    .chart-type-selector button.active:hover {
        background: #444;
    }

    .chart-container {
        position: relative;
        margin-top: 20px;
    }
</style>

<script>
    // Color palette
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF9F40'
    ];

    // Initialize chart data
    const categoryData = <?php echo $category_json; ?>;
    const monthlyData = <?php echo $monthly_json; ?>;

    // Utility functions
    function formatMoney(amount) {
        return 'RM ' + parseFloat(amount).toFixed(2);
    }

    function formatMonth(monthStr) {
        const date = new Date(monthStr + '-01');
        return date.toLocaleDateString('en-US', {
            month: 'short',
            year: 'numeric'
        });
    }

    // Chart drawing functions
    function drawPieChart(ctx, data, type = 'pie') {
        const total = data.reduce((sum, item) => sum + parseFloat(item.total_revenue || 0), 0);
        const totalQuantity = data.reduce((sum, item) => sum + parseInt(item.total_quantity || 0), 0);
        const startAngle = -0.5 * Math.PI;
        let currentAngle = startAngle;

        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        const centerX = ctx.canvas.width / 2;
        const centerY = (ctx.canvas.height / 2) + 20;
        const radius = Math.min(centerX, centerY) * 0.55;
        const innerRadius = type === 'doughnut' ? radius * 0.6 : 0;

        // First pass: Calculate label positions and detect overlaps
        const labelPositions = [];
        data.forEach((item, index) => {
            const revenue = parseFloat(item.total_revenue || 0);
            const sliceAngle = (revenue / total) * 2 * Math.PI;
            const midAngle = currentAngle + sliceAngle / 2;
            
            // Calculate initial position
            const labelRadius = radius * 1.5;
            const x = centerX + Math.cos(midAngle) * labelRadius;
            const y = centerY + Math.sin(midAngle) * labelRadius;
            
            labelPositions.push({
                x,
                y,
                midAngle,
                item,
                revenue,
                percentage: ((revenue / total) * 100).toFixed(1)
            });
            
            currentAngle += sliceAngle;
        });

        // Adjust specific label positions based on category
        labelPositions.forEach(pos => {
            if (pos.item.category_name === 'Gaming') {
                // Move Gaming label more to the right and down
                pos.x = pos.x + 50;
                pos.y = pos.y + 20;
            } else if (pos.item.category_name === 'Audio') {
                // Move Audio label more to the left
                pos.x = pos.x - 40;
                pos.y = pos.y + 10;
            } else if (pos.item.category_name === 'Laptops') {
                // Move Laptops label more to the left
                pos.x = pos.x - 60;
                pos.y = pos.y - 30;
            } else if (pos.item.category_name === 'Smartphones') {
                // Move Smartphone label more to the right and down
                pos.x = pos.x + 40;
                pos.y = pos.y - 20;
            }
        });

        // Reset currentAngle for drawing segments
        currentAngle = startAngle;

        // Draw segments
        data.forEach((item, index) => {
            const revenue = parseFloat(item.total_revenue || 0);
            const sliceAngle = (revenue / total) * 2 * Math.PI;

            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
            if (type === 'doughnut') {
                ctx.arc(centerX, centerY, innerRadius, currentAngle + sliceAngle, currentAngle, true);
            }
            ctx.closePath();

            ctx.fillStyle = colors[index % colors.length];
            ctx.fill();

            currentAngle += sliceAngle;
        });

        // Draw labels with collision detection
        labelPositions.forEach((pos, index) => {
            const { x, y, item, revenue, percentage } = pos;
            
            // Calculate label box position with adjusted coordinates
            const labelX = x > centerX ? x + 10 : x - 10;
            const labelY = y;

            // Draw connecting line with curve adjustment
            ctx.beginPath();
            ctx.strokeStyle = '#666';
            ctx.lineWidth = 1;
            const lineStartX = centerX + Math.cos(pos.midAngle) * radius;
            const lineStartY = centerY + Math.sin(pos.midAngle) * radius;
            
            // Adjust control points for smoother curves
            const controlX = centerX + Math.cos(pos.midAngle) * (radius * 1.3);
            const controlY = centerY + Math.sin(pos.midAngle) * (radius * 1.3);
            
            ctx.moveTo(lineStartX, lineStartY);
            ctx.quadraticCurveTo(controlX, controlY, labelX, labelY);
            ctx.stroke();

            // Draw label background with adjusted position
            ctx.fillStyle = 'rgba(255, 255, 255, 0.95)'; // Slightly more opaque background
            const labelWidth = 200;
            const labelHeight = 60;
            const boxX = x > centerX ? labelX : labelX - labelWidth;
            const boxY = labelY - labelHeight / 2;
            
            ctx.fillRect(boxX, boxY, labelWidth, labelHeight);

            // Draw label text
            ctx.fillStyle = '#333';
            ctx.textAlign = x > centerX ? 'left' : 'right';
            ctx.textBaseline = 'middle';
            
            ctx.font = 'bold 13px Arial';
            ctx.fillText(item.category_name, labelX, labelY - 20);
            
            ctx.font = '12px Arial';
            ctx.fillText(
                `Revenue: RM ${Number(revenue).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`,
                labelX,
                labelY
            );
            
            ctx.fillText(
                `Units: ${Number(item.total_quantity).toLocaleString('en-US')} (${percentage}%)`,
                labelX,
                labelY + 20
            );
        });

        // Enhanced center text for doughnut
        if (type === 'doughnut') {
            ctx.fillStyle = '#333';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Draw total units
            ctx.font = 'bold 14px Arial';
            ctx.fillText('Total Units', centerX, centerY - 25);
            ctx.font = 'bold 16px Arial';
            ctx.fillText(totalQuantity.toLocaleString('en-US'), centerX, centerY - 5);
            
            // Draw separator line
            ctx.beginPath();
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            ctx.moveTo(centerX - 40, centerY + 5);
            ctx.lineTo(centerX + 40, centerY + 5);
            ctx.stroke();
            
            // Draw total sales
            ctx.font = 'bold 14px Arial';
            ctx.fillText('Total Sales', centerX, centerY + 25);
            ctx.font = 'bold 16px Arial';
            ctx.fillText('RM ' + total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }), centerX, centerY + 45);
        }
    }

    function drawBarChart(ctx, data, isHorizontal = false) {
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

        const padding = {
            left: 80,
            right: 40,
            top: 40,
            bottom: 60
        };

        const chartWidth = ctx.canvas.width - (padding.left + padding.right);
        const chartHeight = ctx.canvas.height - (padding.top + padding.bottom);

        const validData = data.filter(item =>
            item &&
            item.total_revenue !== undefined &&
            item.total_revenue !== null
        );

        if (isHorizontal) {
            const barHeight = chartHeight / validData.length * 0.7;
            const gap = chartHeight / validData.length * 0.3;

            const maxValue = Math.max(...validData.map(item => parseFloat(item.total_revenue || 0)));
            // Round up maxValue to nearest 5000
            const roundedMaxValue = Math.ceil(maxValue / 5000) * 5000;

            // Draw horizontal grid lines
            ctx.strokeStyle = '#e0e0e0';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 5; i++) {
                const x = padding.left + (chartWidth * i / 5);
                ctx.beginPath();
                ctx.moveTo(x, padding.top);
                ctx.lineTo(x, ctx.canvas.height - padding.bottom);
                ctx.stroke();
                
                // Draw grid value with consistent 5000 intervals
                ctx.fillStyle = '#666';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.font = '11px Arial';
                const value = (roundedMaxValue * i / 5);
                ctx.fillText(
                    'RM ' + value.toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }),
                    x,
                    ctx.canvas.height - padding.bottom + 15
                );
            }

            validData.forEach((item, index) => {
                const barWidth = (parseFloat(item.total_revenue || 0) / roundedMaxValue) * chartWidth;
                const y = padding.top + index * (barHeight + gap);

                // Draw bar
                ctx.fillStyle = colors[index % colors.length];
                ctx.fillRect(padding.left, y, barWidth, barHeight);

                // Draw labels
                ctx.fillStyle = '#333';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 12px Arial';
                ctx.fillText(item.category_name || item.month || '', padding.left - 5, y + barHeight / 2);

                // Draw values
                ctx.textAlign = 'left';
                ctx.font = '12px Arial';
                const revenue = Number(item.total_revenue).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                ctx.fillText(`RM ${revenue}`, padding.left + barWidth + 10, y + barHeight / 2);
                
                if (item.total_quantity) {
                    ctx.fillStyle = '#666';
                    ctx.fillText(
                        `(${Number(item.total_quantity).toLocaleString('en-US')} units)`,
                        padding.left + barWidth + 120,
                        y + barHeight / 2
                    );
                }
            });
        } else {
            // Vertical bar chart implementation
            const barWidth = chartWidth / validData.length * 0.65;
            const gap = chartWidth / validData.length * 0.35;

            const maxValue = Math.max(...validData.map(item => parseFloat(item.total_revenue || 0)));
            // Round up maxValue to nearest 5000
            const roundedMaxValue = Math.ceil(maxValue / 5000) * 5000;

            // Draw horizontal grid lines with consistent 5000 intervals
            ctx.strokeStyle = '#e0e0e0';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 5; i++) {
                const y = ctx.canvas.height - padding.bottom - (chartHeight * i / 5);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(ctx.canvas.width - padding.right, y);
                ctx.stroke();

                // Draw grid value with more space
                ctx.fillStyle = '#666';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                ctx.font = '11px Arial';
                const value = (roundedMaxValue * i / 5);
                ctx.fillText(
                    'RM ' + value.toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }),
                    padding.left - 15,
                    y
                );
            }

            validData.forEach((item, index) => {
                const barHeight = (parseFloat(item.total_revenue || 0) / roundedMaxValue) * chartHeight;
                const x = padding.left + index * (barWidth + gap);

                // Draw bar
                ctx.fillStyle = colors[index % colors.length];
                ctx.fillRect(x, ctx.canvas.height - padding.bottom - barHeight, barWidth, barHeight);

                // Labels with improved spacing
                ctx.fillStyle = '#333';
                ctx.textAlign = 'center';
                ctx.font = '12px Arial';
                
                // Month/Category label with more space
                ctx.textBaseline = 'top';
                const label = item.category_name || formatMonth(item.month) || '';
                ctx.fillText(label, x + barWidth / 2, ctx.canvas.height - padding.bottom + 15);

                // Revenue value with more space
                ctx.textBaseline = 'bottom';
                const revenue = Number(item.total_revenue).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                ctx.fillText(`RM ${revenue}`, x + barWidth / 2, 
                            ctx.canvas.height - padding.bottom - barHeight - 15);

                // Quantity label with more space
                if (item.total_quantity) {
                    ctx.fillStyle = '#666';
                    ctx.fillText(
                        `${Number(item.total_quantity).toLocaleString('en-US')} units`,
                        x + barWidth / 2,
                        ctx.canvas.height - padding.bottom - barHeight - 35
                    );
                }
            });
        }
    }

    // Chart state and toggle functions
    const chartStates = {
        category: 'pie',
        revenue: 'bar',
        comparison: 'bar'
    };

    // Initialize event listeners for chart type buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Add click event listeners to all buttons in chart-type-selector
        document.querySelectorAll('.chart-type-selector button').forEach(button => {
            button.addEventListener('click', function() {
                const chartId = this.parentElement.getAttribute('data-chart');
                const chartType = this.getAttribute('data-type');
                
                // Update chart state
                chartStates[chartId] = chartType;
                
                // Update active button
                this.parentElement.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Redraw charts
                updateCharts();
            });
        });
    });

    function updateCharts() {
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        drawPieChart(categoryCtx, categoryData, chartStates.category);

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        drawBarChart(revenueCtx, monthlyData);

        // Comparison Chart
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        drawBarChart(comparisonCtx, categoryData, chartStates.comparison === 'horizontal-bar');
    }

    // Initialize charts on load
    window.addEventListener('load', () => {
        // Set canvas dimensions
        const canvases = document.querySelectorAll('canvas');
        canvases.forEach(canvas => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        });

        updateCharts();
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        const canvases = document.querySelectorAll('canvas');
        canvases.forEach(canvas => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        });

        updateCharts();
    });
</script>