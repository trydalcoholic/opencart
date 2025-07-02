<?php
$total_time = 0;
foreach ($logs as $log) {
    $total_time += $log['time'];
}
$page_load_time = round((microtime(true) - OPENCART_START) * 1000, 2);
?>
<!-- Google Fonts for JetBrains Mono -->
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap" rel="stylesheet">
<!-- PrismJS for syntax highlighting -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css">
<style>
    #debug-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #272822; /* Matching Prism Okaidia theme */
        color: #f8f8f2;
        border-top: 1px solid #555;
        padding: 10px;
        z-index: 9999;
        font-family: 'JetBrains Mono', 'Menlo', 'Consolas', monospace;
        font-size: 13px;
        max-height: 400px;
        overflow-y: auto;
        box-sizing: border-box;
    }
    #debug-bar table {
        width: 100%;
        border-collapse: collapse;
    }
    #debug-bar th, #debug-bar td {
        border-bottom: 1px solid #444;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }
    #debug-bar .debug-summary {
        margin-bottom: 10px;
    }
    #debug-bar .debug-summary strong {
        color: #66d9ef;
    }
    #debug-bar .debug-summary span {
        margin-right: 15px;
    }
    #debug-bar details {
        margin-top: 5px;
    }
    #debug-bar summary {
        cursor: pointer;
        outline: none;
        white-space: pre-wrap;
        word-break: break-all;
    }
    #debug-bar pre[class*="language-"] {
        padding: 1em;
        margin: .5em 0;
        overflow: auto;
        border-radius: 0.3em;
    }
</style>

<div id="debug-bar">
    <div class="debug-summary">
        <span><strong>Total Page Load:</strong> <?= $page_load_time ?>ms</span>
        <span><strong>SQL Queries:</strong> <?= count($logs) ?></span>
        <span><strong>SQL Time:</strong> <?= round($total_time, 2) ?>ms</span>
    </div>

    <?php $profiler_events = \Debug\Profiler::getEvents(); ?>
    <?php if ($profiler_events): ?>
        <h4 style="color: #e6db74; margin: 15px 0 5px 0;">Controller Profiling</h4>
        <table>
            <thead>
                <tr>
                    <th>Controller</th>
                    <th style="width: 150px; text-align: right;">Execution Time (ms)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiler_events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: right;"><?= round($event['time'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h4 style="color: #e6db74; margin: 15px 0 5px 0;">SQL Queries</h4>
    <table>
        <thead>
            <tr>
                <th>Query</th>
                <th style="width: 100px; text-align: right;">Time (ms)</th>
                <th style="width: 80px; text-align: right;">Rows</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <details>
                            <summary><?= htmlspecialchars($log['sql'], ENT_QUOTES, 'UTF-8') ?></summary>
                            <pre><code class="language-sql"><?= htmlspecialchars($log['sql'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                            <h4>Backtrace:</h4>
                            <pre><code class="language-php"><?php
                                $trace_output = [];
                                foreach ($log['backtrace'] as $trace) {
                                    if (isset($trace['file'])) {
                                        $file = str_replace(dirname(DIR_SYSTEM) . '/', '', $trace['file']);
                                        $line = $trace['line'];
                                        $function = (isset($trace['class']) ? $trace['class'] . $trace['type'] : '') . $trace['function'];
                                        $trace_output[] = "{$file}:{$line} - {$function}()";
                                    }
                                }
                                echo implode("\n", $trace_output);
                            ?></code></pre>
                        </details>
                    </td>
                    <td style="text-align: right;"><?= round($log['time'], 2) ?></td>
                    <td style="text-align: right;"><?= $log['rows'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- PrismJS scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
<script>
    // Defer highlighting to ensure all content is ready
    setTimeout(() => {
        Prism.highlightAll();
    }, 0);
</script>
