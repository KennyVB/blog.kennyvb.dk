jQuery(function($){
      $(".tweet").tweet({
        join_text: "auto",
        username: "kennyvb",
        count: 10,
        auto_join_text_default: "jeg sagde,",
        auto_join_text_ed: "jeg",
        auto_join_text_ing: "jeg er",
        auto_join_text_reply: "jeg svarede",
        auto_join_text_url: "Jeg checked ud",
        loading_text: "loading tweets..."
      });
    });
