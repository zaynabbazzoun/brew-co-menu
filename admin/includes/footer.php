      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
      document.querySelectorAll('select.js-search').forEach(function (el) {
        new TomSelect(el, {
          allowEmptyOption: true,
          plugins: ['dropdown_input'],
        });
      });
    </script>
  </body>
</html>