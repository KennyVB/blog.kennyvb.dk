jQuery(function($){
      $(".tweet").tweet({
        join_text: "auto",
        username: "kennyvb",
        avatar_size: 24,
        count: 3,
        auto_join_text_default: "jeg sagde,",
        auto_join_text_ed: "jeg",
        auto_join_text_ing: "jeg er",
        auto_join_text_reply: "jeg svared",
        auto_join_text_url: "we were checking out",
        loading_text: "loading tweets..."
      });
    });
