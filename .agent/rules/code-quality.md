---
trigger: always_on
---

make sure you only write professional solid, dry, kiss code which is scalable!
might want to create a custom component in src/Views/components and add custom styling for that component to that file as well just like in the other components. Always make sure styling fits the existing styling of the application.
Keep comments short and precise, for docstrings never explain implementation details but rather how to use it. Never use comments for thinking.

Do never care about backwards compatibility except for making working migrations. When doing migrations you need to make sure to update ALL existing code to the new schema.

- **Error handling**: Use the existing error handling mechanisms for detailed and consistent error messages.