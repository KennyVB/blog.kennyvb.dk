jQuery(function($){
      $(".tweet").tweet({
        join_text: "auto",
        username: "kennyvb",
        avatar_size: 21,
        count: 7,
        auto_join_text_default: "jeg sagde,",
        auto_join_text_ed: "jeg",
        auto_join_text_ing: "jeg er",
        auto_join_text_reply: "jeg svarede",
        auto_join_text_url: "we were checking out",
        loading_text: "loading tweets..."
      });
    });
