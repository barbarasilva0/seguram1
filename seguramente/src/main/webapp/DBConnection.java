import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class DBConnection {

    private static final String USERNAME = "root";
    private static final String PASSWORD = "!5xne5Qui8900";
    private static final String URL_UTILIZADORES = "jdbc:mysql://localhost:3306/segura_utilizadores";
    private static final String URL_JOGOS = "jdbc:mysql://localhost:3306/segura_jogos";

    public static Connection createConnection(String database) {
        Connection con = null;
        String url = database.equalsIgnoreCase("utilizadores") ? URL_UTILIZADORES : URL_JOGOS;

        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            con = DriverManager.getConnection(url, USERNAME, PASSWORD);
        } catch (ClassNotFoundException e) {
            System.err.println("Erro: Driver do MySQL não encontrado.");
            e.printStackTrace();
        } catch (SQLException e) {
            System.err.println("Erro ao conectar ao banco de dados: " + url);
            e.printStackTrace();
        }
        return con;
    }
}
