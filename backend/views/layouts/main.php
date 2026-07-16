<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use backend\assets\AppAsset;
use yii\helpers\Url;

\hail812\adminlte3\assets\FontAwesomeAsset::register($this);
\hail812\adminlte3\assets\AdminLteAsset::register($this);
\hail812\adminlte3\assets\PluginAsset::register($this)->add(['sweetalert2', 'toastr']);
AppAsset::register($this);
$this->registerCssFile('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback');

$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');

$publishedRes = Yii::$app->assetManager->publish('@vendor/hail812/yii2-adminlte3/src/web/js');
$this->registerJsFile($publishedRes[1].'/control_sidebar.js', ['depends' => '\hail812\adminlte3\assets\AdminLteAsset']);

// Register mobile responsive CSS and JS
$this->registerCssFile('@web/css/mobile-responsive-crm.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/mobile.js', ['depends' => [\yii\web\JqueryAsset::class]]);

?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php $this->registerCsrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
        <style>
            .main-sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1037;
                transition: none !important;
            }

            /* Desktop collapsed sidebar */
            @media (min-width: 992px) {
                .sidebar-mini.sidebar-collapse .main-sidebar {
                    width: 4.6rem !important;
                }

                .sidebar-mini.sidebar-collapse .main-sidebar:hover {
                    width: 4.6rem !important;
                }

                .sidebar-mini.sidebar-collapse .main-sidebar:hover .nav-sidebar > .nav-item > .nav-link {
                    width: 4.6rem;
                }

                .sidebar-mini.sidebar-collapse .content-wrapper,
                .sidebar-mini.sidebar-collapse .main-footer {
                    margin-left: 4.6rem !important;
                }

                .sidebar-mini.sidebar-collapse .main-sidebar:hover .nav-sidebar > .nav-item > .nav-link > .nav-icon {
                    margin-left: 0;
                }

                .sidebar-mini.sidebar-collapse .main-sidebar:hover .brand-text,
                .sidebar-mini.sidebar-collapse .main-sidebar:hover .nav-sidebar .nav-link p {
                    display: none !important;
                }
            }

            .main-sidebar .nav-sidebar {
                padding-top: 0;
            }

            .sidebar-loading .main-sidebar {
                visibility: hidden;
            }

            .main-sidebar.sidebar-ready {
                visibility: visible;
                opacity: 1;
            }
        </style>
    </head>
    <body class="hold-transition sidebar-mini" >
    <?php $this->beginBody() ?>

    <div class="wrapper">
        <!-- Navbar -->
        <?= $this->render('navbar', ['assetDir' => $assetDir]) ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?= $this->render('sidebar', ['assetDir' => $assetDir]) ?>

        <!-- Content Wrapper. Contains page content -->
        <?= $this->render('content', ['content' => $content, 'assetDir' => $assetDir]) ?>
        <!-- /.content-wrapper -->

        <!-- Control Sidebar -->
        <?= $this->render('control-sidebar') ?>
        <!-- /.control-sidebar -->

        <!-- Main Footer -->
        <?= $this->render('footer') ?>
    </div>

    <?php $this->endBody() ?>
    <?php
    $userRoles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
    $isEmployee = isset($userRoles['employee']);
    $baseUrl = Yii::$app->request->baseUrl;

    $employeeChatUrls = [
        'generateWebsocketToken' => $baseUrl . '/my-chat/generate-websocket-token',
        'getUnreadCount' => $baseUrl . '/my-chat/get-unread-count',
        'markAsRead' => $baseUrl . '/my-chat/mark-as-read',
        'chatIndex' => $baseUrl . '/my-chat/index',
        'loadMoreMessages' => $baseUrl . '/my-chat/load-more-messages',
        'sendMessage' => $baseUrl . '/my-chat/send-message',
        'editMessage' => $baseUrl . '/my-chat/edit-message',
        'downloadAttachment' => $baseUrl . '/my-chat/download-attachment',
        'searchMessages' => $baseUrl . '/my-chat/search-messages',
    ];

    $adminChatUrls = [
        'generateWebsocketToken' => $baseUrl . '/chat/generate-websocket-token',
        'getUnreadCount' => $baseUrl . '/chat/get-unread-count',
        'markAsRead' => $baseUrl . '/chat/mark-as-read',
        'chatIndex' => $baseUrl . '/chat/index',
        'loadMoreMessages' => $baseUrl . '/chat/load-more-messages',
        'sendMessage' => $baseUrl . '/chat/send-message',
        'editMessage' => $baseUrl . '/chat/edit-message',
        'createChat' => $baseUrl . '/chat/create-chat',
        'addParticipants' => $baseUrl . '/chat/add-participants',
        'removeParticipant' => $baseUrl . '/chat/remove-participant',
        'searchMessages' => $baseUrl . '/chat/search-messages',
    ];

    $globalWebSocketConfig = [
        'websocketUrl' => Yii::$app->params['websocketUrl'],
        'enableNavbarNotifications' => true,
        'isEmployee' => $isEmployee,
        'urls' => $isEmployee ? $employeeChatUrls : $adminChatUrls,
    ];

    if (strpos(Yii::$app->request->url, '/chat') !== false || strpos(Yii::$app->request->url, '/my-chat') !== false) {
        $chatId = Yii::$app->request->get('id');
        if ($chatId) {
            $globalWebSocketConfig['selectedChatId'] = $chatId;
        }
    }

    $this->registerJs('
    window.globalWebSocketConfig = ' . json_encode($globalWebSocketConfig) . ';
', \yii\web\View::POS_HEAD);

    $this->registerJsFile('@web/js/websocket-client.js?v=6', [
        'position' => \yii\web\View::POS_END
    ]);
    ?>
    </body>
    </html>
<?php $this->endPage() ?>