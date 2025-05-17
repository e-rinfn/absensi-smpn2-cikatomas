 <?php include '../../includes/header.php'; ?>

 <body>
     <div id="app">
         <!-- Sidebar start -->

         <?php include '../../includes/navigation/admin.php'; ?>

         <!-- Sidebar end -->

         <!-- Main start -->

         <div id="main">
             <header class="mb-3">
                 <a href="#" class="burger-btn d-block d-xl-none">
                     <i class="bi bi-justify fs-3"></i>
                 </a>
             </header>

             <div class="page-heading">
                 <h3>Judul Halaman!</h3>
             </div>
             <div class="page-content">
                 <section class="row">
                     <!-- Main content start -->

                     HELLO RIN!

                     <!-- Main content end -->
                 </section>
             </div>
         </div>

         <!-- Main end -->
     </div>


     <!-- Javascript add start -->

     <!-- your javascript code here -->

     <!-- Javascript add end -->

     <!-- Javascript template mazer start -->
     <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
     <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>

     <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
     <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>

     <script src="<?= $base_url ?>/assets/js/main.js"></script>
     <!-- Javascrip template mazer end -->
 </body>